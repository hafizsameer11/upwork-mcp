<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioEmbedding extends Model
{
    protected $fillable = ['portfolio_project_id', 'model', 'content', 'embedding'];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PortfolioProject::class, 'portfolio_project_id');
    }
}
