<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalOutcome extends Model
{
    protected $fillable = ['proposal_id', 'outcome', 'occurred_at', 'notes'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
