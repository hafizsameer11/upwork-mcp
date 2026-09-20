<?php

namespace App\Events;

use App\Models\UpworkJob;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StrongJobMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public UpworkJob $job) {}

    public function broadcastOn(): array
    {
        return [new Channel('dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'job.strong_matched';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->job->id,
            'title' => $this->job->title,
            'overall_match' => $this->job->overall_match,
            'profile_gap' => $this->job->profile_gap,
        ];
    }
}
