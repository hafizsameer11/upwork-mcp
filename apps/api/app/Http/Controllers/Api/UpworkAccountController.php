<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncUpworkMessages;
use App\Jobs\SyncUpworkPortfolio;
use App\Models\UpworkAccount;
use Illuminate\Http\Request;

class UpworkAccountController extends Controller
{
    public function index()
    {
        return UpworkAccount::query()->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string',
            'account_type' => 'nullable|string',
            'tenant_id' => 'nullable|string',
            'access_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            UpworkAccount::query()->update(['is_default' => false]);
        }

        $account = UpworkAccount::query()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'is_active' => true,
        ]);

        return response()->json($account, 201);
    }

    public function update(Request $request, UpworkAccount $upworkAccount)
    {
        $data = $request->validate([
            'label' => 'sometimes|string',
            'access_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'tenant_id' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'profile_snapshot' => 'nullable|array',
        ]);

        if (($data['is_default'] ?? false) === true) {
            UpworkAccount::query()->update(['is_default' => false]);
        }

        $upworkAccount->update($data);

        return $upworkAccount;
    }

    public function connect(Request $request, UpworkAccount $upworkAccount)
    {
        // Manual fallback if browser OAuth is unavailable
        $data = $request->validate([
            'access_token' => 'required|string',
            'refresh_token' => 'nullable|string',
            'token_expires_at' => 'nullable|date',
            'tenant_id' => 'nullable|string',
        ]);
        $upworkAccount->update($data);

        SyncUpworkPortfolio::dispatch($upworkAccount->id);
        SyncUpworkMessages::dispatch($upworkAccount->id);

        return $upworkAccount->fresh();
    }

    public function sync(UpworkAccount $upworkAccount)
    {
        SyncUpworkPortfolio::dispatch($upworkAccount->id);
        SyncUpworkMessages::dispatch($upworkAccount->id);

        return ['queued' => true];
    }
}
