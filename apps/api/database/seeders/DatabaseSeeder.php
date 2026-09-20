<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\SearchProfile;
use App\Models\UpworkAccount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@hmstech.local'],
            ['name' => 'BD Manager', 'password' => Hash::make('password'), 'role' => 'manager']
        );

        User::query()->updateOrCreate(
            ['email' => 'bd@hmstech.local'],
            ['name' => 'BD Agent', 'password' => Hash::make('password'), 'role' => 'bd']
        );

        $account = UpworkAccount::query()->updateOrCreate(
            ['label' => 'Primary Freelancer'],
            [
                'user_id' => $manager->id,
                'account_type' => 'freelancer',
                'is_active' => true,
                'is_default' => true,
                'profile_snapshot' => [
                    'title' => 'Full Stack Developer',
                    'skills' => ['Laravel', 'PHP', 'React', 'Node.js', 'WordPress', 'MySQL'],
                    'overview' => 'Agency full-stack team specializing in Laravel SaaS and commerce migrations.',
                ],
            ]
        );

        $profiles = [
            [
                'name' => 'Laravel / SaaS',
                'include_keywords' => ['Laravel', 'PHP', 'SaaS', 'REST API', 'Multi-tenant', 'Stripe', 'MySQL'],
                'exclude_keywords' => ['Tutor', 'Student assignment', '$20 fixes', 'Crypto token'],
                'min_budget' => 300,
                'max_job_age_hours' => 2,
                'alert_threshold' => 85,
            ],
            [
                'name' => 'React / Node',
                'include_keywords' => ['React', 'Next.js', 'Node.js', 'Express', 'SaaS', 'Dashboard', 'API'],
                'exclude_keywords' => ['Tutor', 'Student'],
                'min_budget' => 300,
                'max_job_age_hours' => 2,
                'alert_threshold' => 85,
            ],
            [
                'name' => 'WordPress / WooCommerce',
                'include_keywords' => ['WordPress', 'WooCommerce', 'Elementor', 'Custom plugin', 'API integration', 'Migration'],
                'exclude_keywords' => ['Tutor'],
                'min_budget' => 300,
                'max_job_age_hours' => 4,
                'alert_threshold' => 82,
            ],
            [
                'name' => 'React Native',
                'include_keywords' => ['React Native', 'Expo', 'iOS', 'Android', 'Mobile app'],
                'exclude_keywords' => ['Tutor'],
                'min_budget' => 500,
                'max_job_age_hours' => 4,
                'alert_threshold' => 82,
            ],
        ];

        foreach ($profiles as $profile) {
            SearchProfile::query()->updateOrCreate(
                ['name' => $profile['name'], 'upwork_account_id' => $account->id],
                [...$profile, 'upwork_account_id' => $account->id, 'is_enabled' => true, 'payment_verified_preferred' => true]
            );
        }

        AppSetting::setValue('watch_frequency', 15);
        AppSetting::setValue('slack', [
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'events' => [
                'strong_match' => true,
                'awaiting_approval' => true,
                'submitted' => true,
                'client_reply' => true,
                'offer' => true,
                'hourly_digest' => true,
                'daily_summary' => true,
            ],
        ]);

        $this->call(HmsTechPortfolioSeeder::class);
    }
}
