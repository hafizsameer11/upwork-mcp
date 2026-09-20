<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncUpworkPortfolio;
use App\Models\PortfolioProject;
use App\Services\Portfolio\PortfolioSearchService;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(private readonly PortfolioSearchService $portfolio) {}

    public function index(Request $request)
    {
        $query = PortfolioProject::query()->with(['skills', 'features'])->latest();
        if ($source = $request->string('source')->toString()) {
            $query->where('source', $source);
        }

        return $query->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'raw_notes' => 'nullable|string',
            'website_url' => 'nullable|url',
            'industry' => 'nullable|string',
            'project_type' => 'nullable|string',
            'upwork_account_id' => 'nullable|integer',
            'enrich' => 'boolean',
        ]);

        $project = PortfolioProject::query()->create([
            ...collect($data)->except('enrich')->all(),
            'source' => 'INTERNAL',
            'visibility_internal' => true,
        ]);

        if ($request->boolean('enrich', true) && ($project->raw_notes || $project->description)) {
            try {
                $project = $this->portfolio->enrichFromNotes($project);
            } catch (\Throwable) {
                $this->portfolio->upsertEmbedding($project);
            }
        }

        return response()->json($project->load(['skills', 'features']), 201);
    }

    public function show(PortfolioProject $portfolio)
    {
        return $portfolio->load(['skills', 'features', 'embedding']);
    }

    public function enrich(PortfolioProject $portfolio)
    {
        return $this->portfolio->enrichFromNotes($portfolio);
    }

    public function sync(Request $request)
    {
        $accountId = $request->integer('upwork_account_id');
        SyncUpworkPortfolio::dispatch($accountId);

        return ['queued' => true];
    }
}
