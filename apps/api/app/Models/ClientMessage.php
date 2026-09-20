<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMessage extends Model
{
    protected $fillable = [
        'upwork_account_id', 'upwork_job_id', 'proposal_id', 'room_id', 'message_id',
        'direction', 'message_type', 'body', 'sent_at', 'is_read', 'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'is_read' => 'boolean',
            'raw_payload' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(UpworkAccount::class, 'upwork_account_id');
    }
}
