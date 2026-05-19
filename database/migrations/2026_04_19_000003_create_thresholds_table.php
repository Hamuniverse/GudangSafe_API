<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menyimpan konfigurasi batas aman suhu & kelembaban gudang.
     * Tabel ini hanya memiliki SATU baris aktif (konfigurasi terkini).
     * Admin dapat mengubah threshold via endpoint PUT /api/threshold.
     *
     * Referensi batas aman penyimpanan pupuk & pestisida:
     * - Suhu     : 15°C – 30°C (ideal gudang pertanian)
     * - Kelembaban: 40% – 70% (mencegah penggumpalan & degradasi)
     */
    public function up(): void
    {
        Schema::create('thresholds', function (Blueprint $table) {
            $table->id();

            // Batas minimum suhu (°C) — default 15°C
            $table->float('temp_min', 5, 2)->default(15.00);

            // Batas maksimum suhu (°C) — default 30°C
            $table->float('temp_max', 5, 2)->default(30.00);

            // Batas minimum kelembaban (%) — default 40%
            $table->float('humidity_min', 5, 2)->default(40.00);

            // Batas maksimum kelembaban (%) — default 70%
            $table->float('humidity_max', 5, 2)->default(70.00);

            // ID admin yang terakhir mengubah threshold
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thresholds');
    }
};
