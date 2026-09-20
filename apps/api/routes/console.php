<?php

use App\Jobs\SearchUpworkJobs;
use App\Jobs\SendDailySlackSummary;
use App\Jobs\SyncUpworkMessages;
use App\Models\AppSetting;
use App\Models\UpworkAccount;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    if (! config('upwork.polling_enabled')) {
        return;
    }
    SearchUpworkJobs::dispatch();
})->everyFifteenMinutes()->name('upwork-job-watcher')->withoutOverlapping();

Schedule::call(function () {
    if (! config('upwork.polling_enabled')) {
        return;
    }
    try {
        $minutes = (int) (AppSetting::getValue('watch_frequency', 15) ?: 15);
    } catch (\Throwable) {
        $minutes = 15;
    }
    // Frequency is primarily controlled via UPWORK_WATCH_FREQUENCY / settings UI docs;
    // schedule stays every 15 and job no-ops when polling disabled.
    unset($minutes);

    UpworkAccount::query()->where('is_active', true)->each(function ($account) {
        SyncUpworkMessages::dispatch($account->id);
    });
})->everyThirtyMinutes()->name('upwork-message-sync')->withoutOverlapping();

Schedule::job(new SendDailySlackSummary)->dailyAt('18:00');
