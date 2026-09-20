<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\Proposal;
use App\Models\UpworkJob;
use App\Services\Slack\SlackNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDailySlackSummary implements ShouldQueue
{
    use Queueable;

    public function handle(SlackNotifier $slack): void
    {
        $today = AnalyticsDaily::query()->firstOrCreate(['date' => now()->toDateString()]);
        $awaiting = Proposal::query()->where('status', 'AWAITING_APPROVAL')->count();
        $strong = UpworkJob::query()
            ->whereDate('created_at', now()->toDateString())
            ->where('overall_match', '>=', config('upwork.strong_match_threshold'))
            ->count();

        $slack->send('daily_summary', 'Upwork BD daily summary', [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Daily summary*\nJobs found: {$today->jobs_found}\nStrong matches: {$strong}\nProposals: {$today->proposals}\nReplies: {$today->replies}\nAwaiting approval: {$awaiting}",
                ],
            ],
        ]);
    }
}
