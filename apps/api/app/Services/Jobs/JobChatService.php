<?php

namespace App\Services\Jobs;

use App\Models\JobChatMessage;
use App\Models\PortfolioProject;
use App\Models\UpworkJob;
use App\Models\User;
use App\Services\Ai\OpenAiService;
use App\Services\Portfolio\PortfolioSearchService;

class JobChatService
{
    public function __construct(
        private readonly OpenAiService $ai,
        private readonly PortfolioSearchService $portfolioSearch,
    ) {}

    public function history(UpworkJob $job)
    {
        return JobChatMessage::query()
            ->where('upwork_job_id', $job->id)
            ->orderBy('id')
            ->get();
    }

    public function ask(UpworkJob $job, User $user, string $message): JobChatMessage
    {
        $job->loadMissing(['account', 'analysis', 'skills']);

        JobChatMessage::query()->create([
            'upwork_job_id' => $job->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $portfolioHits = $this->portfolioSearch->similarToJob($job, 5);
        $portfolio = collect($portfolioHits)->map(function ($hit) {
            $p = ! empty($hit['id']) ? PortfolioProject::query()->with('skills')->find($hit['id']) : null;

            return [
                'title' => $p?->title ?? ($hit['title'] ?? null),
                'skills' => $p?->skills?->pluck('skill')->all() ?? [],
                'description' => mb_substr($p?->description ?? '', 0, 400),
            ];
        })->all();

        $history = JobChatMessage::query()
            ->where('upwork_job_id', $job->id)
            ->orderBy('id')
            ->limit(30)
            ->get()
            ->map(fn (JobChatMessage $m) => [
                'role' => $m->role === 'assistant' ? 'assistant' : 'user',
                'content' => $m->content,
            ])
            ->all();

        $system = <<<'PROMPT'
You are an Upwork BD writing coach for HMSTECH agency.
You help craft and improve personalized proposals for ONE specific job.

You have the full job, our profile, portfolio, and prior AI analysis.
Be specific — cite real projects from our portfolio when relevant.
When asked to generate/improve a proposal, output a ready-to-send cover letter.
Keep proposals human, concise, non-generic, and tailored to the job description.
Never claim we submitted anything. Never invent client facts.
If the user pastes a draft, improve it and explain what changed briefly.
PROMPT;

        $context = json_encode([
            'job' => [
                'title' => $job->title,
                'description' => $job->description,
                'budget_min' => $job->budget_min,
                'budget_max' => $job->budget_max,
                'experience_level' => $job->experience_level,
                'skills' => $job->skills->pluck('skill')->all(),
                'scores' => [
                    'overall' => $job->overall_match,
                    'technical' => $job->technical_match,
                    'portfolio' => $job->portfolio_match,
                    'profile' => $job->profile_match,
                    'profile_gap' => $job->profile_gap,
                ],
            ],
            'analysis' => $job->analysis?->only([
                'summary', 'thoughts', 'recommendation', 'win_strategy',
                'must_haves', 'nice_to_haves', 'red_flags', 'reasons', 'weaknesses',
                'portfolio_evidence',
            ]),
            'our_profile' => $job->account?->profile_snapshot,
            'our_portfolio' => $portfolio,
        ], JSON_UNESCAPED_UNICODE);

        $messages = [
            ['role' => 'system', 'content' => $system."\n\nCONTEXT JSON:\n".$context],
            ...$history,
        ];

        $reply = $this->ai->reply($messages, [
            'entity_type' => UpworkJob::class,
            'entity_id' => $job->id,
            'action' => 'job_chat',
        ]);

        $meta = [];
        if (preg_match('/```(?:proposal|cover.?letter)?\s*([\s\S]*?)```/i', $reply, $m)) {
            $meta['proposal_draft'] = trim($m[1]);
        }

        return JobChatMessage::query()->create([
            'upwork_job_id' => $job->id,
            'user_id' => null,
            'role' => 'assistant',
            'content' => $reply,
            'meta' => $meta ?: null,
        ]);
    }
}
