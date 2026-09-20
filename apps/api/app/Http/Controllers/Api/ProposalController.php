<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\ProposalOutcome;
use App\Models\SkillPerformance;
use App\Models\UpworkJob;
use App\Services\Proposals\ProposalService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function __construct(private readonly ProposalService $proposals) {}

    public function index(Request $request)
    {
        $query = Proposal::query()->with(['job', 'aiReview', 'creator', 'approver'])->latest();
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return $query->paginate(25);
    }

    public function show(Proposal $proposal)
    {
        return $proposal->load(['job.analysis', 'versions', 'portfolios.project', 'approvals.user', 'aiReview', 'outcomes']);
    }

    public function generate(Request $request, UpworkJob $job)
    {
        $proposal = $this->proposals->generateStrategies($job, $request->user());

        return response()->json($proposal, 201);
    }

    public function update(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'cover_letter' => 'sometimes|string',
            'ai_strategy' => 'sometimes|string',
            'proposed_rate' => 'nullable|numeric',
            'estimated_duration' => 'nullable|string',
            'screening_answers' => 'nullable|array',
            'proposal_connects' => 'nullable|integer',
            'boost_connects' => 'nullable|integer',
            'selected_version_id' => 'nullable|integer',
            'portfolio_ids' => 'nullable|array',
        ]);

        if (! empty($data['selected_version_id'])) {
            $proposal->versions()->update(['is_selected' => false]);
            $version = $proposal->versions()->whereKey($data['selected_version_id'])->firstOrFail();
            $version->update(['is_selected' => true]);
            $data['cover_letter'] = $version->cover_letter;
            $data['ai_strategy'] = $version->ai_strategy;
        }

        if (isset($data['portfolio_ids'])) {
            $proposal->portfolios()->delete();
            foreach ($data['portfolio_ids'] as $pid) {
                $proposal->portfolios()->create([
                    'portfolio_project_id' => $pid,
                    'is_selected' => true,
                ]);
            }
            unset($data['portfolio_ids']);
        }

        unset($data['selected_version_id']);
        $proposal->update($data);

        return $proposal->fresh(['versions', 'portfolios.project']);
    }

    public function submitForApproval(Request $request, Proposal $proposal)
    {
        return $this->proposals->submitForApproval($proposal, $request->user(), $request->input('notes'));
    }

    public function approve(Request $request, Proposal $proposal)
    {
        return $this->proposals->approve($proposal, $request->user(), $request->input('notes'));
    }

    public function reject(Request $request, Proposal $proposal)
    {
        return $this->proposals->reject($proposal, $request->user(), $request->input('notes'));
    }

    public function submit(Request $request, Proposal $proposal)
    {
        return $this->proposals->submitToUpwork($proposal);
    }

    public function reanalyze(Proposal $proposal)
    {
        return $this->proposals->reviewSubmitted($proposal);
    }

    public function outcome(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'outcome' => 'required|in:reply,interview,hired,lost,no_response',
            'notes' => 'nullable|string',
        ]);

        $outcome = ProposalOutcome::query()->create([
            'proposal_id' => $proposal->id,
            'outcome' => $data['outcome'],
            'occurred_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        if (in_array($data['outcome'], ['interview', 'hired'], true)) {
            $proposal->job?->update(['status' => strtoupper($data['outcome'] === 'hired' ? 'HIRED' : 'INTERVIEW')]);
        }
        if ($data['outcome'] === 'lost') {
            $proposal->job?->update(['status' => 'LOST']);
        }

        if ($proposal->ai_strategy) {
            $row = SkillPerformance::query()->firstOrCreate([
                'skill' => 'general',
                'strategy' => $proposal->ai_strategy,
            ]);
            if ($data['outcome'] === 'reply' || $data['outcome'] === 'interview' || $data['outcome'] === 'hired') {
                $row->increment('replies');
            }
            if ($data['outcome'] === 'hired') {
                $row->increment('hires');
            }
        }

        return $outcome;
    }
}
