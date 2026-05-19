<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Threshold extends Model
{
    protected $fillable = [
        'temp_min',
        'temp_max',
        'humidity_min',
        'humidity_max',
        'updated_by',
    ];

    protected $casts = [
        'temp_min'     => 'float',
        'temp_max'     => 'float',
        'humidity_min' => 'float',
        'humidity_max' => 'float',
    ];

    // Relasi

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper

    /**
     * Selalu ambil baris pertama (hanya ada 1 konfigurasi aktif).
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'temp_min'     => 15.00,
                'temp_max'     => 30.00,
                'humidity_min' => 40.00,
                'humidity_max' => 70.00,
            ]
        );
    }
}
