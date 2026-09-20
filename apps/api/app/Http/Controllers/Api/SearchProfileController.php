<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchProfile;
use Illuminate\Http\Request;

class SearchProfileController extends Controller
{
    public function index()
    {
        return SearchProfile::query()->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'upwork_account_id' => 'nullable|integer',
            'include_keywords' => 'nullable|array',
            'exclude_keywords' => 'nullable|array',
            'min_budget' => 'nullable|integer',
            'max_job_age_hours' => 'nullable|integer',
            'payment_verified_preferred' => 'boolean',
            'alert_threshold' => 'nullable|integer|min:1|max:100',
            'is_enabled' => 'boolean',
        ]);

        return response()->json(SearchProfile::query()->create($data), 201);
    }

    public function update(Request $request, SearchProfile $searchProfile)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'include_keywords' => 'nullable|array',
            'exclude_keywords' => 'nullable|array',
            'min_budget' => 'nullable|integer',
            'max_job_age_hours' => 'nullable|integer',
            'payment_verified_preferred' => 'boolean',
            'alert_threshold' => 'nullable|integer|min:1|max:100',
            'is_enabled' => 'boolean',
        ]);
        $searchProfile->update($data);

        return $searchProfile;
    }

    public function destroy(SearchProfile $searchProfile)
    {
        $searchProfile->delete();

        return response()->json(['ok' => true]);
    }
}
