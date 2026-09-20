<?php

namespace App\Services\Portfolio;

use App\Models\PortfolioEmbedding;
use App\Models\PortfolioFeature;
use App\Models\PortfolioProject;
use App\Models\PortfolioSkill;
use App\Models\UpworkJob;
use App\Services\Ai\OpenAiService;
use Illuminate\Support\Facades\DB;

class PortfolioSearchService
{
    public function __construct(private readonly OpenAiService $ai) {}

    public function similarToJob(UpworkJob $job, int $limit = 5): array
    {
        $query = trim(($job->title ?? '')."\n".($job->description ?? ''));
        if ($query === '') {
            return [];
        }

        try {
            $vector = $this->ai->embed($query);
        } catch (\Throwable) {
            return PortfolioProject::query()
                ->with('skills')
                ->limit($limit)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'score' => 0.5,
                    'why' => 'Fallback listing (embeddings unavailable)',
                ])->all();
        }

        if (DB::getDriverName() === 'pgsql' && $vector) {
            $literal = '['.implode(',', $vector).']';
            $rows = DB::select(
                'SELECT pe.portfolio_project_id, pe.content, 1 - (pe.embedding <=> ?::vector) AS score
                 FROM portfolio_embeddings pe
                 WHERE pe.embedding IS NOT NULL
                 ORDER BY pe.embedding <=> ?::vector
                 LIMIT ?',
                [$literal, $literal, $limit]
            );

            return collect($rows)->map(function ($row) {
                $project = PortfolioProject::query()->find($row->portfolio_project_id);

                return [
                    'id' => $row->portfolio_project_id,
                    'title' => $project?->title,
                    'score' => round((float) $row->score, 4),
                    'why' => 'Semantic similarity',
                ];
            })->all();
        }

        // JSON fallback cosine in PHP
        $all = PortfolioEmbedding::query()->with('project')->get();
        $scored = $all->map(function (PortfolioEmbedding $emb) use ($vector) {
            $score = $this->cosine($vector, $emb->embedding ?? []);

            return [
                'id' => $emb->portfolio_project_id,
                'title' => $emb->project?->title,
                'score' => round($score, 4),
                'why' => 'Cosine similarity',
            ];
        })->sortByDesc('score')->take($limit)->values()->all();

        return $scored;
    }

    public function enrichFromNotes(PortfolioProject $project): PortfolioProject
    {
        $notes = $project->raw_notes ?: $project->description;
        if (! $notes) {
            return $project;
        }

        $result = $this->ai->chat([
            [
                'role' => 'system',
                'content' => 'Extract portfolio metadata as JSON: stack (array), categories (array), capabilities (array), features (array), challenges (array), results (array), industry (string), project_type (string), keywords (array).',
            ],
            ['role' => 'user', 'content' => $notes],
        ], [
            'entity_type' => PortfolioProject::class,
            'entity_id' => $project->id,
            'action' => 'enrich_portfolio',
        ]);

        $project->update([
            'industry' => $result['industry'] ?? $project->industry,
            'project_type' => $result['project_type'] ?? $project->project_type,
            'metadata' => array_merge($project->metadata ?? [], $result),
        ]);

        $project->skills()->delete();
        foreach ($result['stack'] ?? [] as $skill) {
            PortfolioSkill::query()->create([
                'portfolio_project_id' => $project->id,
                'skill' => $skill,
            ]);
        }

        $project->features()->delete();
        foreach (['features' => 'feature', 'challenges' => 'challenge', 'results' => 'result', 'capabilities' => 'capability', 'keywords' => 'keyword'] as $key => $type) {
            foreach ($result[$key] ?? [] as $value) {
                PortfolioFeature::query()->create([
                    'portfolio_project_id' => $project->id,
                    'type' => $type,
                    'value' => $value,
                ]);
            }
        }

        $this->upsertEmbedding($project);

        return $project->fresh(['skills', 'features', 'embedding']);
    }

    public function upsertEmbedding(PortfolioProject $project): void
    {
        $content = collect([
            $project->title,
            $project->description,
            $project->industry,
            $project->project_type,
            $project->skills()->pluck('skill')->implode(', '),
            $project->features()->pluck('value')->implode(', '),
        ])->filter()->implode("\n");

        try {
            $vector = $this->ai->embed($content);
        } catch (\Throwable) {
            return;
        }

        $emb = PortfolioEmbedding::query()->updateOrCreate(
            ['portfolio_project_id' => $project->id],
            [
                'model' => config('upwork.openai.embedding_model'),
                'content' => $content,
                'embedding' => $vector,
            ]
        );

        if (DB::getDriverName() === 'pgsql' && $vector) {
            $literal = '['.implode(',', $vector).']';
            DB::update('UPDATE portfolio_embeddings SET embedding = ?::vector WHERE id = ?', [$literal, $emb->id]);
        }
    }

    private function cosine(array $a, array $b): float
    {
        if (! $a || ! $b || count($a) !== count($b)) {
            return 0.0;
        }
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        foreach ($a as $i => $v) {
            $dot += $v * $b[$i];
            $na += $v * $v;
            $nb += $b[$i] * $b[$i];
        }
        if ($na == 0 || $nb == 0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }
}
