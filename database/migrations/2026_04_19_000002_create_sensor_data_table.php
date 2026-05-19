<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menyimpan setiap pembacaan sensor DHT22 (suhu & kelembaban)
     * yang dikirim ESP32 via MQTT topic: gudang/sensor
     */
    public function up(): void
    {
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id();

            // Data suhu dalam Celsius dari DHT22
            $table->float('temperature', 5, 2);

            // Data kelembaban relatif (%) dari DHT22
            $table->float('humidity', 5, 2);

            /**
             * Status kondisi gudang berdasarkan threshold:
             * - normal   : suhu & kelembaban dalam batas aman
             * - warning  : mendekati batas threshold (10% dari batas)
             * - danger   : melewati batas threshold
             */
            $table->enum('status', ['normal', 'warning', 'danger'])->default('normal');

            // ID user yang memicu pembacaan (null = otomatis oleh ESP32)
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Waktu tepat saat ESP32 membaca sensor (bisa beda dg created_at)
            $table->timestamp('recorded_at');

            // Waktu data masuk ke database Laravel
            $table->timestamp('created_at')->useCurrent();

            // Index untuk query history yang sering dipakai
            $table->index('recorded_at');
            $table->index('status');
            $table->index(['recorded_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};
