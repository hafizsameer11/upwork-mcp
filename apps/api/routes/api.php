<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\SearchProfileController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UpworkAccountController;
use App\Http\Controllers\Api\UpworkOAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard/today', [DashboardController::class, 'today']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);
    Route::post('/jobs/{job}/skip', [JobController::class, 'skip']);
    Route::post('/jobs/{job}/reanalyze', [JobController::class, 'reanalyze']);
    Route::get('/jobs/{job}/chat', [JobController::class, 'chatHistory']);
    Route::post('/jobs/{job}/chat', [JobController::class, 'chat']);
    Route::post('/jobs/{job}/proposals', [ProposalController::class, 'generate']);

    Route::get('/proposals', [ProposalController::class, 'index']);
    Route::get('/proposals/{proposal}', [ProposalController::class, 'show']);
    Route::patch('/proposals/{proposal}', [ProposalController::class, 'update']);
    Route::post('/proposals/{proposal}/submit-for-approval', [ProposalController::class, 'submitForApproval']);
    Route::post('/proposals/{proposal}/approve', [ProposalController::class, 'approve']);
    Route::post('/proposals/{proposal}/reject', [ProposalController::class, 'reject']);
    Route::post('/proposals/{proposal}/submit', [ProposalController::class, 'submit']);
    Route::post('/proposals/{proposal}/reanalyze', [ProposalController::class, 'reanalyze']);
    Route::post('/proposals/{proposal}/outcome', [ProposalController::class, 'outcome']);

    Route::apiResource('search-profiles', SearchProfileController::class)->except(['show']);

    Route::get('/portfolio', [PortfolioController::class, 'index']);
    Route::post('/portfolio', [PortfolioController::class, 'store']);
    Route::get('/portfolio/{portfolio}', [PortfolioController::class, 'show']);
    Route::post('/portfolio/{portfolio}/enrich', [PortfolioController::class, 'enrich']);
    Route::post('/portfolio/sync', [PortfolioController::class, 'sync']);

    Route::get('/accounts', [UpworkAccountController::class, 'index']);
    Route::post('/accounts', [UpworkAccountController::class, 'store']);
    Route::patch('/accounts/{upworkAccount}', [UpworkAccountController::class, 'update']);
    Route::post('/accounts/{upworkAccount}/connect', [UpworkAccountController::class, 'connect']);
    Route::post('/accounts/{upworkAccount}/oauth/start', [UpworkOAuthController::class, 'start']);
    Route::post('/accounts/{upworkAccount}/sync', [UpworkAccountController::class, 'sync']);

    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings', [SettingsController::class, 'update']);
    Route::post('/scheduler/run', [SettingsController::class, 'runWatcher']);
    Route::get('/scheduler/runs', [SettingsController::class, 'schedulerRuns']);
    Route::get('/messages', [SettingsController::class, 'messages']);
    Route::get('/analytics', [SettingsController::class, 'analytics']);
});
