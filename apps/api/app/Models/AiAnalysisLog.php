<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAnalysisLog extends Model
{
    protected $fillable = [
        'entity_type', 'entity_id', 'action', 'model', 'prompt_tokens',
        'completion_tokens', 'request', 'response',
    ];

    protected function casts(): array
    {
        return [
            'request' => 'array',
            'response' => 'array',
        ];
    }
}
