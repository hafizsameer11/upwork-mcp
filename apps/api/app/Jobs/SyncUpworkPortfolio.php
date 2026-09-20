<?php

namespace App\Jobs;

use App\Models\UpworkAccount;
use App\Services\Portfolio\PortfolioSearchService;
use App\Services\Upwork\UpworkMcpClient;
use App\Models\PortfolioProject;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncUpworkPortfolio implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $accountId) {}

    public function handle(PortfolioSearchService $portfolio): void
    {
        $account = UpworkAccount::query()->find($this->accountId);
        if (! $account) {
            return;
        }

        $client = new UpworkMcpClient($account);
        $result = $client->getPortfolio();
        $items = $result['items'] ?? $result['portfolio'] ?? $result['data'] ?? [];

        foreach ($items as $item) {
            $project = PortfolioProject::query()->updateOrCreate(
                [
                    'upwork_account_id' => $account->id,
                    'upwork_portfolio_id' => (string) ($item['id'] ?? $item['portfolio_id'] ?? uniqid('uw_')),
                ],
                [
                    'title' => $item['title'] ?? 'Untitled',
                    'description' => $item['description'] ?? null,
                    'source' => 'UPWORK',
                    'website_url' => $item['url'] ?? null,
                    'visibility_upwork' => true,
                    'metadata' => $item,
                ]
            );
            $portfolio->upsertEmbedding($project);
        }

        $profile = $client->getProfile();
        if ($profile) {
            $account->update(['profile_snapshot' => $profile]);
        }
    }
}
