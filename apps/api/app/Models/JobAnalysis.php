<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAnalysis extends Model
{
    protected $fillable = [
        'upwork_job_id', 'requirements', 'reasons', 'weaknesses',
        'portfolio_evidence', 'score_breakdown', 'summary', 'raw_ai_response',
        'thoughts', 'recommendation', 'win_strategy',
        'must_haves', 'nice_to_haves', 'red_flags',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'reasons' => 'array',
            'weaknesses' => 'array',
            'portfolio_evidence' => 'array',
            'score_breakdown' => 'array',
            'raw_ai_response' => 'array',
            'must_haves' => 'array',
            'nice_to_haves' => 'array',
            'red_flags' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(UpworkJob::class, 'upwork_job_id');
    }
}
