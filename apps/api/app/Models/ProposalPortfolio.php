<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalPortfolio extends Model
{
    protected $fillable = ['proposal_id', 'portfolio_project_id', 'is_selected'];

    protected function casts(): array
    {
        return ['is_selected' => 'boolean'];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PortfolioProject::class, 'portfolio_project_id');
    }
}
