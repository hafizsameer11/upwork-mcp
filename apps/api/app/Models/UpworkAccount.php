<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UpworkAccount extends Model
{
    protected $fillable = [
        'user_id', 'label', 'account_type', 'upwork_user_id', 'tenant_id',
        'access_token', 'refresh_token', 'token_expires_at', 'profile_snapshot',
        'is_active', 'is_default',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $appends = ['is_connected'];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'profile_snapshot' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function getIsConnectedAttribute(): bool
    {
        return filled($this->attributes['access_token'] ?? null);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(UpworkJob::class);
    }

    public function searchProfiles(): HasMany
    {
        return $this->hasMany(SearchProfile::class);
    }
}
