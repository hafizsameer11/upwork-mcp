<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McpToolLog extends Model
{
    protected $fillable = [
        'upwork_account_id', 'tool_name', 'arguments', 'result', 'status', 'error',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'result' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(UpworkAccount::class, 'upwork_account_id');
    }
}
