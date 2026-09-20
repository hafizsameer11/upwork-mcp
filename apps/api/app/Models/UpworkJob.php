<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UpworkJob extends Model
{
    protected $table = 'upwork_jobs';

    protected $fillable = [
        'upwork_account_id', 'search_profile_id', 'upwork_job_id', 'title', 'description', 'url',
        'budget_type', 'budget_min', 'budget_max', 'experience_level', 'client_country',
        'client_spent', 'client_hire_rate', 'client_rating', 'client_payment_verified',
        'proposal_count', 'posted_at', 'discovered_at', 'technical_match', 'profile_match',
        'portfolio_match', 'client_quality', 'budget_fit', 'freshness_score', 'competition_score',
        'overall_match', 'profile_gap', 'status', 'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'discovered_at' => 'datetime',
            'client_payment_verified' => 'boolean',
            'profile_gap' => 'boolean',
            'raw_payload' => 'array',
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'technical_match' => 'decimal:2',
            'profile_match' => 'decimal:2',
            'portfolio_match' => 'decimal:2',
            'overall_match' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(UpworkAccount::class, 'upwork_account_id');
    }

    public function searchProfile(): BelongsTo
    {
        return $this->belongsTo(SearchProfile::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(JobSkill::class, 'upwork_job_id');
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(JobAnalysis::class, 'upwork_job_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'upwork_job_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(JobChatMessage::class, 'upwork_job_id');
    }
}
