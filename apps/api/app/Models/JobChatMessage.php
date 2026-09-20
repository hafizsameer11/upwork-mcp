<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobChatMessage extends Model
{
    protected $fillable = [
        'upwork_job_id', 'user_id', 'role', 'content', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(UpworkJob::class, 'upwork_job_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
