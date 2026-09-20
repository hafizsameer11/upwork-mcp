<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\SchedulerRun;
use App\Models\SearchProfile;
use App\Models\UpworkAccount;
use App\Models\UpworkJob;
use App\Services\Matching\MatchingEngine;
use App\Services\Slack\SlackNotifier;
use App\Services\Upwork\UpworkMcpClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SearchUpworkJobs implements ShouldQueue
{
    use Queueable;

    public function handle(MatchingEngine $matching, SlackNotifier $slack): void
    {
        if (! config('upwork.polling_enabled')) {
            return;
        }

        $run = SchedulerRun::query()->create([
            'job_name' => 'SearchUpworkJobs',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $found = 0;
        $strong = 0;

        try {
            $accounts = UpworkAccount::query()->where('is_active', true)->get();
            foreach ($accounts as $account) {
                $profiles = SearchProfile::query()
                    ->where('is_enabled', true)
                    ->where(function ($q) use ($account) {
                        $q->whereNull('upwork_account_id')->orWhere('upwork_account_id', $account->id);
                    })
                    ->get();

                $client = new UpworkMcpClient($account);

                foreach ($profiles as $profile) {
                    $result = $client->searchJobs([
                        'q' => implode(' ', $profile->include_keywords ?? []),
                        'exclude' => $profile->exclude_keywords ?? [],
                        'min_budget' => $profile->min_budget,
                        'max_age_hours' => $profile->max_job_age_hours,
                    ]);

                    $jobs = $result['jobs'] ?? $result['items'] ?? $result['data'] ?? [];
                    foreach ($jobs as $raw) {
                        $externalId = (string) ($raw['id'] ?? $raw['job_id'] ?? $raw['ciphertext'] ?? Str::uuid());
                        $job = UpworkJob::query()->firstOrNew([
                            'upwork_account_id' => $account->id,
                            'upwork_job_id' => $externalId,
                        ]);

                        if ($job->exists) {
                            continue;
                        }

                        $job->fill([
                            'search_profile_id' => $profile->id,
                            'title' => $raw['title'] ?? 'Untitled job',
                            'description' => $raw['description'] ?? $raw['snippet'] ?? null,
                            'url' => $raw['url'] ?? null,
                            'budget_type' => $raw['budget_type'] ?? null,
                            'budget_min' => $raw['budget_min'] ?? $raw['amount']['min'] ?? null,
                            'budget_max' => $raw['budget_max'] ?? $raw['amount']['max'] ?? null,
                            'experience_level' => $raw['experience_level'] ?? null,
                            'client_country' => $raw['client']['country'] ?? null,
                            'client_spent' => $raw['client']['spent'] ?? null,
                            'client_hire_rate' => $raw['client']['hire_rate'] ?? null,
                            'client_rating' => $raw['client']['rating'] ?? null,
                            'client_payment_verified' => $raw['client']['payment_verified'] ?? null,
                            'proposal_count' => $raw['proposal_count'] ?? null,
                            'posted_at' => isset($raw['posted_at']) ? now()->parse($raw['posted_at']) : now(),
                            'discovered_at' => now(),
                            'status' => 'NEW',
                            'raw_payload' => $raw,
                        ])->save();

                        $found++;
                        AnalyzeJob::dispatch($job->id);
                    }
                }
            }

            $run->update([
                'status' => 'ok',
                'finished_at' => now(),
                'meta' => ['found' => $found, 'strong' => $strong],
            ]);

            AnalyticsDaily::query()->updateOrCreate(
                ['date' => now()->toDateString()],
                []
            );
            AnalyticsDaily::query()->where('date', now()->toDateString())->increment('jobs_found', $found);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'error',
                'finished_at' => now(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
