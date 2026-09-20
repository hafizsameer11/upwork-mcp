<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalAiReview extends Model
{
    protected $fillable = [
        'proposal_id', 'quality_score', 'strengths', 'weaknesses', 'suggestions',
        'flags', 'summary', 'raw_ai_response', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'strengths' => 'array',
            'weaknesses' => 'array',
            'suggestions' => 'array',
            'flags' => 'array',
            'raw_ai_response' => 'array',
            'generated_at' => 'datetime',
            'quality_score' => 'decimal:2',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
