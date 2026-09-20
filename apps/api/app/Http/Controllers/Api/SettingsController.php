<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SearchUpworkJobs;
use App\Models\AppSetting;
use App\Models\ClientMessage;
use App\Models\SchedulerRun;
use App\Models\SkillPerformance;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show()
    {
        return [
            'polling_enabled' => config('upwork.polling_enabled'),
            'frequency_minutes' => AppSetting::getValue('watch_frequency', config('upwork.default_frequency_minutes')),
            'slack' => AppSetting::getValue('slack', [
                'webhook_url' => config('upwork.slack.webhook_url'),
                'events' => [
                    'strong_match' => true,
                    'awaiting_approval' => true,
                    'submitted' => true,
                    'client_reply' => true,
                    'offer' => true,
                    'hourly_digest' => true,
                    'daily_summary' => true,
                ],
            ]),
            'openai_configured' => (bool) config('upwork.openai.api_key'),
            'mcp_url' => config('upwork.mcp_url'),
        ];
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'frequency_minutes' => 'nullable|integer|in:15,30,60',
            'slack' => 'nullable|array',
        ]);

        if (isset($data['frequency_minutes'])) {
            AppSetting::setValue('watch_frequency', $data['frequency_minutes']);
        }
        if (isset($data['slack'])) {
            AppSetting::setValue('slack', $data['slack']);
        }

        return $this->show();
    }

    public function runWatcher()
    {
        if (! config('upwork.polling_enabled')) {
            return response()->json([
                'error' => 'UPWORK_POLLING_ENABLED is false. Contact Upwork MCP Support before enabling production polling.',
            ], 422);
        }

        SearchUpworkJobs::dispatch();

        return ['queued' => true];
    }

    public function schedulerRuns()
    {
        return SchedulerRun::query()->latest()->limit(50)->get();
    }

    public function messages(Request $request)
    {
        return ClientMessage::query()->latest('sent_at')->paginate(50);
    }

    public function analytics()
    {
        return [
            'strategies' => SkillPerformance::query()->orderByDesc('replies')->get(),
        ];
    }
}
