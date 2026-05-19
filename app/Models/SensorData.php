<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SensorData extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'temperature',
        'humidity',
        'status',
        'recorded_by',
        'recorded_at',
        'created_at',
    ];

    protected $casts = [
        'temperature' => 'float',
        'humidity'    => 'float',
        'recorded_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    // Relasi

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function anomalyLogs(): HasMany
    {
        return $this->hasMany(AnomalyLog::class);
    }

    // Helper

    /**
     * Hitung status kondisi berdasarkan threshold.
     * Dipanggil saat menyimpan data baru dari MQTT.
     */
    public static function calculateStatus(float $temp, float $humidity, Threshold $threshold): string
    {
        $tempOk     = $temp >= $threshold->temp_min && $temp <= $threshold->temp_max;
        $humidityOk = $humidity >= $threshold->humidity_min && $humidity <= $threshold->humidity_max;

        if (!$tempOk || !$humidityOk) {
            // Cek apakah melewati lebih dari 10% dari batas
            $tempPercent     = self::overPercent($temp, $threshold->temp_min, $threshold->temp_max);
            $humidityPercent = self::overPercent($humidity, $threshold->humidity_min, $threshold->humidity_max);

            if ($tempPercent > 10 || $humidityPercent > 10) {
                return 'danger';
            }
            return 'warning';
        }

        return 'normal';
    }

    private static function overPercent(float $value, float $min, float $max): float
    {
        if ($value < $min) {
            return (($min - $value) / $min) * 100;
        }
        if ($value > $max) {
            return (($value - $max) / $max) * 100;
        }
        return 0;
    }
}
