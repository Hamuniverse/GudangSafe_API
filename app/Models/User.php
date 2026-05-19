<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'fcm_token',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password'  => 'hashed',
        'is_active' => 'boolean',
    ];

    // Helper

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    // Relasi

    public function sensorData(): HasMany
    {
        return $this->hasMany(SensorData::class, 'recorded_by');
    }

    public function actuatorLogs(): HasMany
    {
        return $this->hasMany(ActuatorLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function anomalyLogs(): HasMany
    {
        return $this->hasMany(AnomalyLog::class);
    }

    public function threshold(): HasOne
    {
        return $this->hasOne(Threshold::class, 'updated_by');
    }
}
