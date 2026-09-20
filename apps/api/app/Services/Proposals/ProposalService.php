<?php

namespace App\Services\Proposals;

use App\Models\Proposal;
use App\Models\ProposalAiReview;
use App\Models\ProposalApproval;
use App\Models\ProposalPortfolio;
use App\Models\ProposalVersion;
use App\Models\SkillPerformance;
use App\Models\UpworkJob;
use App\Models\User;
use App\Services\Ai\OpenAiService;
use App\Services\Portfolio\PortfolioSearchService;
use App\Services\Slack\SlackNotifier;
use App\Services\Upwork\UpworkMcpClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProposalService
{
    public function __construct(
        private readonly OpenAiService $ai,
        private readonly PortfolioSearchService $portfolioSearch,
        private readonly SlackNotifier $slack,
    ) {}

    public function generateStrategies(UpworkJob $job, User $user): Proposal
    {
        $portfolioHits = $this->portfolioSearch->similarToJob($job, 5);
        $learning = SkillPerformance::query()
            ->orderByDesc('replies')
            ->limit(10)
            ->get()
            ->toArray();

        $result = $this->ai->chat([
            [
                'role' => 'system',
                'content' => 'Create three genuinely different Upwork proposal strategies as JSON: {strategies:[{strategy:"experience|problem|technical", cover_letter:string, rationale:string}], recommended_rate:number, estimated_duration:string, screening_answers:object}. Do not paraphrase the same letter three times.',
            ],
            [
                'role' => 'user',
                'content' => json_encode([
                    'job' => $job->only(['title', 'description', 'budget_min', 'budget_max', 'experience_level']),
                    'profile' => $job->account?->profile_snapshot,
                    'portfolio' => $portfolioHits,
                    'historical_learning' => $learning,
                ]),
            ],
        ], [
            'entity_type' => UpworkJob::class,
            'entity_id' => $job->id,
            'action' => 'generate_proposals',
        ]);

        return DB::transaction(function () use ($job, $user, $result, $portfolioHits) {
            $proposal = Proposal::query()->create([
                'upwork_job_id' => $job->id,
                'upwork_account_id' => $job->upwork_account_id,
                'created_by' => $user->id,
                'status' => 'DRAFT',
                'proposed_rate' => $result['recommended_rate'] ?? null,
                'estimated_duration' => $result['estimated_duration'] ?? null,
                'screening_answers' => $result['screening_answers'] ?? [],
            ]);

            foreach ($result['strategies'] ?? [] as $i => $strategy) {
                ProposalVersion::query()->create([
                    'proposal_id' => $proposal->id,
                    'ai_strategy' => $strategy['strategy'] ?? 'experience',
                    'cover_letter' => $strategy['cover_letter'] ?? '',
                    'metadata' => $strategy,
                    'is_selected' => $i === 0,
                ]);
            }

            $first = $proposal->versions()->where('is_selected', true)->first();
            if ($first) {
                $proposal->update([
                    'ai_strategy' => $first->ai_strategy,
                    'cover_letter' => $first->cover_letter,
                ]);
            }

            foreach (array_slice($portfolioHits, 0, 3) as $hit) {
                if (! empty($hit['id'])) {
                    ProposalPortfolio::query()->create([
                        'proposal_id' => $proposal->id,
                        'portfolio_project_id' => $hit['id'],
                        'is_selected' => true,
                    ]);
                }
            }

            $job->update(['status' => 'DRAFTED']);

            return $proposal->load(['versions', 'portfolios.project', 'job']);
        });
    }

    public function submitForApproval(Proposal $proposal, User $user, ?string $notes = null): Proposal
    {
        $proposal->update(['status' => 'AWAITING_APPROVAL']);
        ProposalApproval::query()->create([
            'proposal_id' => $proposal->id,
            'user_id' => $user->id,
            'action' => 'submit_for_approval',
            'notes' => $notes,
        ]);
        $proposal->job?->update(['status' => 'AWAITING_APPROVAL']);
        $this->slack->send('awaiting_approval', "Proposal awaiting approval: {$proposal->job?->title}", [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Awaiting approval*\n{$proposal->job?->title}\nStrategy: {$proposal->ai_strategy}",
                ],
            ],
            [
                'type' => 'actions',
                'elements' => [[
                    'type' => 'button',
                    'text' => ['type' => 'plain_text', 'text' => 'Review'],
                    'url' => rtrim(config('upwork.frontend_url'), '/').'/proposals/'.$proposal->id,
                ]],
            ],
        ]);

        return $proposal->fresh();
    }

    public function approve(Proposal $proposal, User $manager, ?string $notes = null): Proposal
    {
        if (! $manager->isManager()) {
            throw new RuntimeException('Only managers/admins can approve proposals.');
        }

        $proposal->update([
            'status' => 'APPROVED',
            'approved_by' => $manager->id,
            'approved_at' => now(),
        ]);
        ProposalApproval::query()->create([
            'proposal_id' => $proposal->id,
            'user_id' => $manager->id,
            'action' => 'approve',
            'notes' => $notes,
        ]);
        $proposal->job?->update(['status' => 'APPROVED']);

        return $proposal->fresh();
    }

    public function reject(Proposal $proposal, User $manager, ?string $notes = null): Proposal
    {
        if (! $manager->isManager()) {
            throw new RuntimeException('Only managers/admins can reject proposals.');
        }

        $proposal->update(['status' => 'DRAFT', 'approved_by' => null, 'approved_at' => null]);
        ProposalApproval::query()->create([
            'proposal_id' => $proposal->id,
            'user_id' => $manager->id,
            'action' => 'reject',
            'notes' => $notes,
        ]);

        return $proposal->fresh();
    }

    /**
     * HARD GATE: never submit unless APPROVED + approved_by set.
     */
    public function submitToUpwork(Proposal $proposal): Proposal
    {
        if (! $proposal->canSubmitToUpwork()) {
            throw new RuntimeException('Proposal is not approved. AI/BD cannot submit without manager approval.');
        }

        $account = $proposal->account;
        $client = new UpworkMcpClient($account);

        $result = $client->submitProposal([
            'job_id' => $proposal->job?->upwork_job_id,
            'cover_letter' => $proposal->cover_letter,
            'proposed_rate' => $proposal->proposed_rate,
            'screening_answers' => $proposal->screening_answers,
            'proposal_connects' => $proposal->proposal_connects,
            'boost_connects' => $proposal->boost_connects,
        ]);

        $proposal->update([
            'status' => 'SUBMITTED',
            'submitted_at' => now(),
            'upwork_proposal_id' => $result['id'] ?? $result['proposal_id'] ?? null,
        ]);
        $proposal->job?->update(['status' => 'SUBMITTED']);

        $this->reviewSubmitted($proposal);
        $this->slack->send('submitted', "Proposal submitted: {$proposal->job?->title}");

        return $proposal->fresh(['aiReview', 'job']);
    }

    public function reviewSubmitted(Proposal $proposal): ProposalAiReview
    {
        $result = $this->ai->chat([
            [
                'role' => 'system',
                'content' => 'Critique a submitted Upwork proposal. Return JSON: quality_score (0-100), strengths (array), weaknesses (array), suggestions (array), flags (array), summary (string).',
            ],
            [
                'role' => 'user',
                'content' => json_encode([
                    'job' => $proposal->job?->only(['title', 'description', 'overall_match', 'profile_match', 'portfolio_match', 'technical_match', 'profile_gap']),
                    'proposal' => $proposal->only(['cover_letter', 'ai_strategy', 'proposed_rate', 'screening_answers']),
                    'profile' => $proposal->account?->profile_snapshot,
                    'portfolios' => $proposal->portfolios()->with('project')->get(),
                ]),
            ],
        ], [
            'entity_type' => Proposal::class,
            'entity_id' => $proposal->id,
            'action' => 'review_proposal',
        ]);

        return ProposalAiReview::query()->updateOrCreate(
            ['proposal_id' => $proposal->id],
            [
                'quality_score' => $result['quality_score'] ?? null,
                'strengths' => $result['strengths'] ?? [],
                'weaknesses' => $result['weaknesses'] ?? [],
                'suggestions' => $result['suggestions'] ?? [],
                'flags' => $result['flags'] ?? [],
                'summary' => $result['summary'] ?? null,
                'raw_ai_response' => $result,
                'generated_at' => now(),
            ]
        );
    }
}
