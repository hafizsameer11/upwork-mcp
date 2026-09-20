<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\ClientMessage;
use App\Models\Proposal;
use App\Models\UpworkJob;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function today(Request $request)
    {
        $today = now()->toDateString();
        $stats = AnalyticsDaily::query()->firstOrCreate(['date' => $today]);

        return [
            'jobs_found' => UpworkJob::query()->whereDate('discovered_at', $today)->count(),
            'strong_matches' => UpworkJob::query()->whereDate('discovered_at', $today)
                ->where('overall_match', '>=', config('upwork.strong_match_threshold'))->count(),
            'drafts' => Proposal::query()->where('status', 'DRAFT')->count(),
            'awaiting_approval' => Proposal::query()->where('status', 'AWAITING_APPROVAL')->count(),
            'submitted' => Proposal::query()->whereDate('submitted_at', $today)->count(),
            'replies' => $stats->replies ?? 0,
            'unread_messages' => ClientMessage::query()->where('is_read', false)->where('direction', 'inbound')->count(),
            'strong_jobs' => UpworkJob::query()
                ->where('overall_match', '>=', config('upwork.strong_match_threshold'))
                ->latest('discovered_at')
                ->limit(10)
                ->get(),
            'awaiting' => Proposal::query()->with('job')->where('status', 'AWAITING_APPROVAL')->latest()->limit(10)->get(),
        ];
    }
}
