<?php

namespace App\Services\Matching;

use App\Models\JobAnalysis;
use App\Models\PortfolioProject;
use App\Models\UpworkJob;
use App\Services\Ai\OpenAiService;
use App\Services\Portfolio\PortfolioSearchService;

class MatchingEngine
{
    public function __construct(
        private readonly OpenAiService $ai,
        private readonly PortfolioSearchService $portfolioSearch,
    ) {}

    public function analyze(UpworkJob $job): JobAnalysis
    {
        $job->loadMissing(['account', 'skills', 'searchProfile']);

        $portfolioHits = $this->portfolioSearch->similarToJob($job, 5);
        // Enrich hits with project details for the LLM
        $portfolioContext = collect($portfolioHits)->map(function ($hit) {
            $project = ! empty($hit['id'])
                ? PortfolioProject::query()->with(['skills', 'features'])->find($hit['id'])
                : null;

            return [
                'id' => $hit['id'] ?? null,
                'title' => $project?->title ?? ($hit['title'] ?? null),
                'similarity' => $hit['score'] ?? null,
                'industry' => $project?->industry,
                'project_type' => $project?->project_type,
                'description' => $project?->description,
                'skills' => $project?->skills?->pluck('skill')->all() ?? [],
                'features' => $project?->features?->where('type', 'feature')->pluck('value')->take(8)->all() ?? [],
                'capabilities' => $project?->features?->where('type', 'capability')->pluck('value')->all() ?? [],
            ];
        })->all();

        $profile = $job->account?->profile_snapshot ?? [];
        $weights = config('upwork.match_weights');
        $heuristic = $this->heuristicScores($job, $portfolioHits, $profile);

        $aiResult = [];
        $aiError = null;
        try {
            $aiResult = $this->ai->chat([
                [
                    'role' => 'system',
                    'content' => <<<'PROMPT'
You are a senior Upwork BD analyst for an agency (HMSTECH).
Read the FULL job description carefully. Do not skim.
Evaluate whether WE can win this job using our Upwork profile evidence AND our internal portfolio.

Return JSON with ALL keys:
- technical, portfolio, profile, client, budget, freshness, competition: numbers 0-100
- overall_thought: short 1-2 sentence verdict
- thoughts: long detailed analysis (8-15 sentences). Cover: what client wants, scope clarity, stack fit, portfolio proof, profile gaps, client quality, competition, risks, and whether to pursue.
- recommendation: "pursue" | "maybe" | "skip"
- win_strategy: concrete approach for proposal angle if we pursue
- must_haves: array of hard requirements extracted from the job
- nice_to_haves: array of optional/nice skills
- requirements: array of key requirements (skills/deliverables)
- reasons: array of strengths / why we match
- weaknesses: array of gaps / risks
- red_flags: array of warning signs (vague scope, unpaid test, tiny budget, etc.) — empty array if none
- portfolio_evidence: array of {title, score, why} referencing our projects
- profile_gap: boolean — true if we can likely do the work but Upwork profile does not show enough evidence
- summary: 2-3 sentence executive summary for the dashboard
PROMPT,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'job' => [
                            'title' => $job->title,
                            'full_description' => $job->description,
                            'url' => $job->url,
                            'budget_type' => $job->budget_type,
                            'budget_min' => $job->budget_min,
                            'budget_max' => $job->budget_max,
                            'experience_level' => $job->experience_level,
                            'proposal_count' => $job->proposal_count,
                            'posted_at' => optional($job->posted_at)?->toIso8601String(),
                            'skills' => $job->skills->pluck('skill')->all(),
                            'search_profile' => $job->searchProfile?->only([
                                'name', 'include_keywords', 'exclude_keywords', 'min_budget',
                            ]),
                        ],
                        'client' => [
                            'country' => $job->client_country,
                            'spent' => $job->client_spent,
                            'hire_rate' => $job->client_hire_rate,
                            'rating' => $job->client_rating,
                            'payment_verified' => $job->client_payment_verified,
                        ],
                        'our_upwork_profile' => $profile,
                        'our_portfolio_projects' => $portfolioContext,
                        'score_weights' => $weights,
                        'heuristic_hint' => $heuristic,
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ], [
                'entity_type' => UpworkJob::class,
                'entity_id' => $job->id,
                'action' => 'match_job_deep',
                'temperature' => 0.3,
            ]);
        } catch (\Throwable $e) {
            $aiError = $e->getMessage();
            $aiResult = $heuristic;
            $aiResult['thoughts'] = 'AI analysis unavailable ('.$aiError.'). Showing heuristic scores only. Configure OPENAI_API_KEY and re-analyze.';
            $aiResult['recommendation'] = 'maybe';
            $aiResult['win_strategy'] = null;
            $aiResult['must_haves'] = [];
            $aiResult['nice_to_haves'] = [];
            $aiResult['red_flags'] = ['AI analysis failed — verify manually'];
            $aiResult['summary'] = $aiResult['summary'] ?? 'Heuristic-only analysis.';
        }

        $scores = [
            'technical' => (float) ($aiResult['technical'] ?? $heuristic['technical']),
            'portfolio' => (float) ($aiResult['portfolio'] ?? $heuristic['portfolio']),
            'profile' => (float) ($aiResult['profile'] ?? $heuristic['profile']),
            'client' => (float) ($aiResult['client'] ?? $heuristic['client']),
            'budget' => (float) ($aiResult['budget'] ?? $heuristic['budget']),
            'freshness' => (float) ($aiResult['freshness'] ?? $heuristic['freshness']),
            'competition' => (float) ($aiResult['competition'] ?? $heuristic['competition']),
        ];

        $overall = round(
            ($scores['technical'] * $weights['technical']
                + $scores['portfolio'] * $weights['portfolio']
                + $scores['profile'] * $weights['profile']
                + $scores['client'] * $weights['client']
                + $scores['budget'] * $weights['budget']
                + $scores['freshness'] * $weights['freshness']
                + $scores['competition'] * $weights['competition']) / 100,
            2
        );

        $profileGap = (bool) ($aiResult['profile_gap'] ?? ($scores['technical'] >= 75 && $scores['profile'] < 55));

        $job->update([
            'technical_match' => $scores['technical'],
            'portfolio_match' => $scores['portfolio'],
            'profile_match' => $scores['profile'],
            'client_quality' => $scores['client'],
            'budget_fit' => $scores['budget'],
            'freshness_score' => $scores['freshness'],
            'competition_score' => $scores['competition'],
            'overall_match' => $overall,
            'profile_gap' => $profileGap,
            'status' => $overall >= config('upwork.medium_match_threshold') ? 'SHORTLISTED' : 'ANALYZED',
        ]);

        $breakdown = [];
        foreach ($scores as $key => $value) {
            $breakdown[$key] = [
                'score' => $value,
                'weight' => $weights[$key],
                'weighted' => round($value * $weights[$key] / 100, 2),
            ];
        }
        $breakdown['overall'] = $overall;

        $thoughts = $aiResult['thoughts']
            ?? $aiResult['overall_thought']
            ?? $aiResult['summary']
            ?? null;

        return JobAnalysis::query()->updateOrCreate(
            ['upwork_job_id' => $job->id],
            [
                'requirements' => $aiResult['requirements'] ?? [],
                'reasons' => $aiResult['reasons'] ?? [],
                'weaknesses' => $aiResult['weaknesses'] ?? [],
                'portfolio_evidence' => $aiResult['portfolio_evidence'] ?? $portfolioHits,
                'score_breakdown' => $breakdown,
                'summary' => $aiResult['summary'] ?? $aiResult['overall_thought'] ?? null,
                'thoughts' => is_array($thoughts) ? implode("\n", $thoughts) : $thoughts,
                'recommendation' => $aiResult['recommendation'] ?? null,
                'win_strategy' => $aiResult['win_strategy'] ?? null,
                'must_haves' => $aiResult['must_haves'] ?? [],
                'nice_to_haves' => $aiResult['nice_to_haves'] ?? [],
                'red_flags' => $aiResult['red_flags'] ?? [],
                'raw_ai_response' => array_merge($aiResult, ['_error' => $aiError]),
            ]
        );
    }

    private function heuristicScores(UpworkJob $job, array $portfolioHits, array $profile): array
    {
        $topPortfolio = $portfolioHits[0]['score'] ?? 0.4;
        if ($topPortfolio <= 1) {
            $topPortfolio *= 100;
        }
        $skills = collect($profile['skills'] ?? [])->map(fn ($s) => strtolower(is_array($s) ? ($s['name'] ?? '') : $s));
        $text = strtolower(($job->title ?? '').' '.($job->description ?? ''));
        $hits = $skills->filter(fn ($s) => $s && str_contains($text, $s))->count();
        $technical = min(95, 40 + ($hits * 8) + ($topPortfolio * 0.2));
        $profileScore = min(95, 35 + ($hits * 10));
        $client = 50;
        if ($job->client_payment_verified) {
            $client += 20;
        }
        if (($job->client_spent ?? 0) > 1000) {
            $client += 15;
        }
        if (($job->client_hire_rate ?? 0) > 50) {
            $client += 10;
        }
        $budget = 60;
        if (($job->budget_min ?? 0) >= 300 || ($job->budget_max ?? 0) >= 300) {
            $budget = 80;
        }
        $hours = $job->posted_at ? now()->diffInHours($job->posted_at) : 24;
        $freshness = max(10, 100 - ($hours * 10));
        $competition = max(20, 100 - (($job->proposal_count ?? 10) * 3));

        return [
            'technical' => round($technical, 2),
            'portfolio' => round(min(95, $topPortfolio), 2),
            'profile' => round($profileScore, 2),
            'client' => min(100, $client),
            'budget' => $budget,
            'freshness' => $freshness,
            'competition' => $competition,
            'reasons' => ['Heuristic scoring used (AI unavailable or partial).'],
            'weaknesses' => [],
            'requirements' => [],
            'portfolio_evidence' => $portfolioHits,
            'summary' => 'Heuristic match analysis.',
            'profile_gap' => $technical >= 75 && $profileScore < 55,
        ];
    }
}
