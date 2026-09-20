<?php

namespace App\Services\Slack;

use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotifier
{
    public function send(string $eventType, string $text, array $blocks = []): bool
    {
        $settings = AppSetting::getValue('slack', []);
        $enabledEvents = $settings['events'] ?? [
            'strong_match' => true,
            'awaiting_approval' => true,
            'submitted' => true,
            'client_reply' => true,
            'offer' => true,
            'hourly_digest' => true,
            'daily_summary' => true,
        ];

        if (($enabledEvents[$eventType] ?? true) === false) {
            return false;
        }

        $webhook = $settings['webhook_url'] ?? config('upwork.slack.webhook_url');
        if (! $webhook) {
            Log::warning('Slack webhook not configured; skipping alert', ['event' => $eventType]);

            return false;
        }

        $payload = ['text' => $text];
        if ($blocks) {
            $payload['blocks'] = $blocks;
        }

        $response = Http::timeout(15)->post($webhook, $payload);
        $ok = $response->successful();

        DB::table('notifications_log')->insert([
            'channel' => 'slack',
            'event_type' => $eventType,
            'payload' => json_encode($payload),
            'status' => $ok ? 'sent' : 'failed',
            'error' => $ok ? null : $response->body(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $ok;
    }

    public function strongMatch(array $job): void
    {
        $url = rtrim(config('upwork.frontend_url'), '/').'/jobs/'.$job['id'];
        $this->send('strong_match', "Strong Upwork Match — {$job['overall_match']}%", [
            [
                'type' => 'header',
                'text' => ['type' => 'plain_text', 'text' => "🔥 Strong Match — {$job['overall_match']}%"],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*{$job['title']}*\nBudget: {$job['budget']}\nPosted: {$job['posted']}\nTechnical: {$job['technical_match']}% · Profile: {$job['profile_match']}% · Portfolio: {$job['portfolio_match']}%",
                ],
            ],
            [
                'type' => 'actions',
                'elements' => [[
                    'type' => 'button',
                    'text' => ['type' => 'plain_text', 'text' => 'Review Job'],
                    'url' => $url,
                ]],
            ],
        ]);
    }
}
