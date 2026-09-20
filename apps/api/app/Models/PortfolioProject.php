<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PortfolioProject extends Model
{
    protected $fillable = [
        'upwork_account_id', 'title', 'description', 'source', 'upwork_portfolio_id',
        'website_url', 'industry', 'project_type', 'status', 'raw_notes', 'metadata',
        'visibility_internal', 'visibility_upwork',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'visibility_internal' => 'boolean',
            'visibility_upwork' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(UpworkAccount::class, 'upwork_account_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(PortfolioSkill::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(PortfolioFeature::class);
    }

    public function embedding(): HasOne
    {
        return $this->hasOne(PortfolioEmbedding::class);
    }
}
