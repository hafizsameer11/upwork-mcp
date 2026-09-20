<?php

namespace App\Jobs;

use App\Events\StrongJobMatched;
use App\Models\AnalyticsDaily;
use App\Models\UpworkJob;
use App\Services\Matching\MatchingEngine;
use App\Services\Slack\SlackNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $jobId) {}

    public function handle(MatchingEngine $matching, SlackNotifier $slack): void
    {
        $job = UpworkJob::query()->with('account')->find($this->jobId);
        if (! $job) {
            return;
        }

        $matching->analyze($job);
        $job->refresh();

        $threshold = $job->searchProfile?->alert_threshold ?? config('upwork.strong_match_threshold');

        if (($job->overall_match ?? 0) >= $threshold) {
            AnalyticsDaily::query()->firstOrCreate(['date' => now()->toDateString()]);
            AnalyticsDaily::query()->where('date', now()->toDateString())->increment('strong_matches');

            $slack->strongMatch([
                'id' => $job->id,
                'title' => $job->title,
                'overall_match' => $job->overall_match,
                'technical_match' => $job->technical_match,
                'profile_match' => $job->profile_match,
                'portfolio_match' => $job->portfolio_match,
                'budget' => trim(($job->budget_min ?? '?').' - '.($job->budget_max ?? '?')),
                'posted' => optional($job->posted_at)->diffForHumans() ?? 'unknown',
            ]);

            event(new StrongJobMatched($job));
        }
    }
}
