<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menyimpan setiap perintah yang dikirim ke aktuator (kipas)
     * beserta hasilnya. Mencatat siapa yang memicu, kapan, dan hasilnya.
     *
     * Alur data:
     * 1. Laravel publish ke MQTT: gudang/actuator/kipas
     * 2. ESP32 eksekusi & publish feedback ke: gudang/actuator/status
     * 3. Laravel update kolom status di baris ini
     */
    public function up(): void
    {
        Schema::create('actuator_logs', function (Blueprint $table) {
            $table->id();

            /**
             * Nama perangkat aktuator:
             * - fan : kipas angin via relay 1 channel
             * (extensible untuk perangkat lain ke depannya)
             */
            $table->enum('device', ['fan'])->default('fan');

            // Perintah yang dikirim: ON atau OFF
            $table->enum('command', ['on', 'off']);

            /**
             * Hasil eksekusi perintah:
             * - pending  : perintah terkirim, menunggu konfirmasi ESP32
             * - success  : ESP32 konfirmasi berhasil
             * - failed   : ESP32 konfirmasi gagal / timeout
             */
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            /**
             * Siapa yang memicu perintah:
             * - 'auto'     : sistem otomatis (threshold terlampaui)
             * - 'manual'   : user mengontrol manual via Flutter
             * - 'schedule' : jadwal otomatis (future feature)
             */
            $table->string('triggered_by', 20)->default('manual');

            // ID user yang mengirim perintah (null jika otomatis)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Catatan tambahan (opsional, misal: alasan override manual)
            $table->text('note')->nullable();

            // Waktu perintah dieksekusi (dikirim ke MQTT)
            $table->timestamp('executed_at')->useCurrent();

            $table->timestamp('created_at')->useCurrent();

            // Index untuk query log yang sering difilter
            $table->index('device');
            $table->index('status');
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actuator_logs');
    }
};
