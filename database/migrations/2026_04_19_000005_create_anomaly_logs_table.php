<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mencatat setiap kejadian anomali sensor (suhu/kelembaban melewati threshold).
     * Data ini dipakai untuk endpoint GET /api/anomaly dan analisis historis.
     *
     * Anomali dicatat otomatis oleh Laravel saat memproses data MQTT
     * dari topic: gudang/sensor
     */
    public function up(): void
    {
        Schema::create('anomaly_logs', function (Blueprint $table) {
            $table->id();

            // Relasi ke data sensor yang memicu anomali
            $table->foreignId('sensor_data_id')
                ->constrained('sensor_data')
                ->cascadeOnDelete();

            // User yang bertanggung jawab saat anomali terjadi (penjaga gudang aktif)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /**
             * Jenis anomali:
             * - temp_high     : suhu melebihi batas maksimum
             * - temp_low      : suhu di bawah batas minimum
             * - humidity_high : kelembaban melebihi batas maksimum
             * - humidity_low  : kelembaban di bawah batas minimum
             */
            $table->enum('type', [
                'temp_high',
                'temp_low',
                'humidity_high',
                'humidity_low'
            ]);

            // Nilai aktual saat anomali terjadi
            $table->float('value', 5, 2);

            // Nilai threshold yang dilanggar
            $table->float('threshold_value', 5, 2);

            /**
             * Tingkat keparahan anomali:
             * - warning  : 1–10% melewati threshold
             * - critical : >10% melewati threshold
             */
            $table->enum('severity', ['warning', 'critical'])->default('warning');

            // Apakah anomali sudah tertangani (suhu/kelembaban kembali normal)
            $table->boolean('is_resolved')->default(false);

            // Waktu anomali berhasil diselesaikan
            $table->timestamp('resolved_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Index untuk filter dan analisis
            $table->index('type');
            $table->index('severity');
            $table->index('is_resolved');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomaly_logs');
    }
};
