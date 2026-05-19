<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menyimpan semua notifikasi yang dikirim ke pengguna.
     * Data ini dipakai untuk endpoint GET /api/notification dan
     * riwayat notifikasi di Flutter.
     *
     * Notifikasi dikirim via:
     * - Push Notification lokal Flutter (FCM)
     * - Haptic Feedback di perangkat Android
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Penerima notifikasi
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Judul notifikasi (ditampilkan sebagai header push notification)
            $table->string('title');

            // Isi pesan notifikasi
            $table->text('message');

            /**
             * Kategori notifikasi:
             * - anomaly  : suhu/kelembaban melewati threshold
             * - actuator : aktuator berhasil/gagal dikendalikan
             * - system   : info sistem (login baru, perubahan threshold, dll)
             */
            $table->enum('type', ['anomaly', 'actuator', 'system'])->default('system');

            // Relasi ke log aktuator (jika notifikasi terkait kontrol kipas)
            $table->foreignId('actuator_log_id')
                ->nullable()
                ->constrained('actuator_logs')
                ->nullOnDelete();

            // Relasi ke anomali (jika notifikasi terkait anomali sensor)
            $table->foreignId('anomaly_log_id')
                ->nullable()
                ->constrained('anomaly_logs')
                ->nullOnDelete();

            // Status baca notifikasi
            $table->boolean('is_read')->default(false);

            // Waktu notifikasi dibaca
            $table->timestamp('read_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Index untuk query notifikasi per user
            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
