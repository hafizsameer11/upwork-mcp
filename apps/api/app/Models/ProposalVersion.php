<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalVersion extends Model
{
    protected $fillable = ['proposal_id', 'ai_strategy', 'cover_letter', 'metadata', 'is_selected'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_selected' => 'boolean',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
