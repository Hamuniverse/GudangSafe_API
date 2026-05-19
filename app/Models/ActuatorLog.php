<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActuatorLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device',
        'command',
        'status',
        'triggered_by',
        'user_id',
        'note',
        'executed_at',
        'created_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    // Relasi

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
