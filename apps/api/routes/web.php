<?php

use App\Http\Controllers\Api\UpworkOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/upwork/oauth/callback', [UpworkOAuthController::class, 'callback'])
    ->name('upwork.oauth.callback');
