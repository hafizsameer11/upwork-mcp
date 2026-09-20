<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Proposal extends Model
{
    protected $fillable = [
        'upwork_job_id', 'upwork_account_id', 'created_by', 'approved_by', 'status',
        'ai_strategy', 'cover_letter', 'screening_answers', 'proposed_rate',
        'estimated_duration', 'milestones', 'attachments', 'proposal_connects',
        'boost_connects', 'upwork_proposal_id', 'submitted_at', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'screening_answers' => 'array',
            'milestones' => 'array',
            'attachments' => 'array',
            'proposed_rate' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(UpworkJob::class, 'upwork_job_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(UpworkAccount::class, 'upwork_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProposalVersion::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(ProposalPortfolio::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ProposalApproval::class);
    }

    public function aiReview(): HasOne
    {
        return $this->hasOne(ProposalAiReview::class);
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(ProposalOutcome::class);
    }

    public function canSubmitToUpwork(): bool
    {
        return $this->status === 'APPROVED' && $this->approved_by !== null;
    }
}
