<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeJob;
use App\Models\UpworkJob;
use App\Services\Jobs\JobChatService;
use App\Services\Matching\MatchingEngine;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(
        private readonly MatchingEngine $matching,
        private readonly JobChatService $chat,
    ) {}

    public function index(Request $request)
    {
        $query = UpworkJob::query()->with(['analysis', 'searchProfile'])->latest('discovered_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($request->boolean('strong')) {
            $query->where('overall_match', '>=', config('upwork.strong_match_threshold'));
        }
        if ($request->boolean('profile_gap')) {
            $query->where('profile_gap', true);
        }

        return $query->paginate(25);
    }

    public function show(UpworkJob $job)
    {
        return $job->load(['analysis', 'skills', 'proposals.versions', 'searchProfile', 'account']);
    }

    public function skip(UpworkJob $job)
    {
        $job->update(['status' => 'SKIPPED']);

        return $job;
    }

    public function reanalyze(Request $request, UpworkJob $job)
    {
        if ($request->boolean('sync')) {
            $analysis = $this->matching->analyze($job->fresh(['account', 'skills', 'searchProfile']));

            return $job->fresh()->load(['analysis', 'skills', 'account']);
        }

        AnalyzeJob::dispatch($job->id);

        return response()->json(['queued' => true]);
    }

    public function chatHistory(UpworkJob $job)
    {
        return $this->chat->history($job);
    }

    public function chat(Request $request, UpworkJob $job)
    {
        $data = $request->validate([
            'message' => 'required|string|min:1|max:8000',
        ]);

        $assistant = $this->chat->ask($job, $request->user(), $data['message']);

        return [
            'message' => $assistant,
            'history' => $this->chat->history($job),
        ];
    }
}
