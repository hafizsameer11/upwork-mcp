<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SearchProfile extends Model
{
    protected $fillable = [
        'upwork_account_id', 'name', 'include_keywords', 'exclude_keywords',
        'min_budget', 'max_job_age_hours', 'payment_verified_preferred',
        'alert_threshold', 'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'include_keywords' => 'array',
            'exclude_keywords' => 'array',
            'payment_verified_preferred' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(UpworkAccount::class, 'upwork_account_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(UpworkJob::class);
    }
}
