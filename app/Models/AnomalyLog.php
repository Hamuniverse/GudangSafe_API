<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sensor_data_id',
        'user_id',
        'type',
        'value',
        'threshold_value',
        'severity',
        'is_resolved',
        'resolved_at',
        'created_at',
    ];

    protected $casts = [
        'value'           => 'float',
        'threshold_value' => 'float',
        'is_resolved'     => 'boolean',
        'resolved_at'     => 'datetime',
        'created_at'      => 'datetime',
    ];

    // Relasi

    public function sensorData(): BelongsTo
    {
        return $this->belongsTo(SensorData::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper

    /**
     * Tentukan severity berdasarkan seberapa jauh nilai melewati threshold.
     */
    public static function determineSeverity(float $value, float $thresholdValue, string $type): string
    {
        $isHigh    = str_ends_with($type, '_high');
        $deviation = $isHigh
            ? (($value - $thresholdValue) / $thresholdValue) * 100
            : (($thresholdValue - $value) / $thresholdValue) * 100;

        return $deviation > 10 ? 'critical' : 'warning';
    }
}
