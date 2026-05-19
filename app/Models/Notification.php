<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'actuator_log_id',
        'anomaly_log_id',
        'is_read',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relasi

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actuatorLog(): BelongsTo
    {
        return $this->belongsTo(ActuatorLog::class);
    }

    public function anomalyLog(): BelongsTo
    {
        return $this->belongsTo(AnomalyLog::class);
    }
}
