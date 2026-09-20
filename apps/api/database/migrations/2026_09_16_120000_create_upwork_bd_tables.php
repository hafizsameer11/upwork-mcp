<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('bd')->after('email'); // bd | manager | admin
        });

        Schema::create('upwork_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->string('account_type')->default('freelancer'); // freelancer|client|agency
            $table->string('upwork_user_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('profile_snapshot')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('search_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->json('include_keywords')->nullable();
            $table->json('exclude_keywords')->nullable();
            $table->unsignedInteger('min_budget')->nullable();
            $table->unsignedInteger('max_job_age_hours')->default(2);
            $table->boolean('payment_verified_preferred')->default(true);
            $table->unsignedTinyInteger('alert_threshold')->default(85);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('upwork_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('upwork_job_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->string('budget_type')->nullable(); // fixed|hourly
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->string('experience_level')->nullable();
            $table->string('client_country')->nullable();
            $table->decimal('client_spent', 12, 2)->nullable();
            $table->decimal('client_hire_rate', 5, 2)->nullable();
            $table->decimal('client_rating', 3, 2)->nullable();
            $table->boolean('client_payment_verified')->nullable();
            $table->unsignedInteger('proposal_count')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->decimal('technical_match', 5, 2)->nullable();
            $table->decimal('profile_match', 5, 2)->nullable();
            $table->decimal('portfolio_match', 5, 2)->nullable();
            $table->decimal('client_quality', 5, 2)->nullable();
            $table->decimal('budget_fit', 5, 2)->nullable();
            $table->decimal('freshness_score', 5, 2)->nullable();
            $table->decimal('competition_score', 5, 2)->nullable();
            $table->decimal('overall_match', 5, 2)->nullable();
            $table->boolean('profile_gap')->default(false);
            $table->string('status')->default('NEW')->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->unique(['upwork_account_id', 'upwork_job_id']);
        });

        Schema::create('job_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_job_id')->constrained('upwork_jobs')->cascadeOnDelete();
            $table->string('skill');
            $table->timestamps();
        });

        Schema::create('job_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_job_id')->constrained('upwork_jobs')->cascadeOnDelete();
            $table->json('requirements')->nullable();
            $table->json('reasons')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('portfolio_evidence')->nullable();
            $table->json('score_breakdown')->nullable();
            $table->text('summary')->nullable();
            $table->json('raw_ai_response')->nullable();
            $table->timestamps();
        });

        Schema::create('portfolio_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source')->default('INTERNAL'); // UPWORK|INTERNAL|BOTH
            $table->string('upwork_portfolio_id')->nullable();
            $table->string('website_url')->nullable();
            $table->string('industry')->nullable();
            $table->string('project_type')->nullable();
            $table->string('status')->default('active');
            $table->text('raw_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('visibility_internal')->default(true);
            $table->boolean('visibility_upwork')->default(false);
            $table->timestamps();
        });

        Schema::create('portfolio_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->string('skill');
            $table->timestamps();
        });

        Schema::create('portfolio_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('feature'); // feature|challenge|result|keyword|capability
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('portfolio_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->string('model')->default('text-embedding-3-small');
            $table->text('content');
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE portfolio_embeddings ADD COLUMN embedding vector(1536)');
            DB::statement('CREATE INDEX portfolio_embeddings_embedding_idx ON portfolio_embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        } else {
            Schema::table('portfolio_embeddings', function (Blueprint $table) {
                $table->json('embedding')->nullable();
            });
        }

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_job_id')->constrained('upwork_jobs')->cascadeOnDelete();
            $table->foreignId('upwork_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('DRAFT')->index();
            $table->string('ai_strategy')->nullable(); // experience|problem|technical
            $table->text('cover_letter')->nullable();
            $table->json('screening_answers')->nullable();
            $table->decimal('proposed_rate', 12, 2)->nullable();
            $table->string('estimated_duration')->nullable();
            $table->json('milestones')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedInteger('proposal_connects')->nullable();
            $table->unsignedInteger('boost_connects')->nullable();
            $table->string('upwork_proposal_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('ai_strategy');
            $table->text('cover_letter');
            $table->json('metadata')->nullable();
            $table->boolean('is_selected')->default(false);
            $table->timestamps();
        });

        Schema::create('proposal_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_selected')->default(true);
            $table->timestamps();
        });

        Schema::create('proposal_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // submit_for_approval|approve|reject
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_ai_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('flags')->nullable();
            $table->text('summary')->nullable();
            $table->json('raw_ai_response')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('outcome'); // reply|interview|hired|lost|no_response
            $table->timestamp('occurred_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('client_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upwork_job_id')->nullable()->constrained('upwork_jobs')->nullOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('room_id')->nullable();
            $table->string('message_id')->nullable();
            $table->string('direction')->default('inbound'); // inbound|outbound
            $table->string('message_type')->default('message'); // message|invitation|offer
            $table->text('body')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->default('slack');
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->string('status')->default('sent');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('scheduler_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name');
            $table->string('status')->default('running');
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_analysis_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });

        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('jobs_found')->default(0);
            $table->unsignedInteger('strong_matches')->default(0);
            $table->unsignedInteger('reviewed')->default(0);
            $table->unsignedInteger('proposals')->default(0);
            $table->unsignedInteger('replies')->default(0);
            $table->unsignedInteger('interviews')->default(0);
            $table->unsignedInteger('hires')->default(0);
            $table->timestamps();
        });

        Schema::create('skill_performance', function (Blueprint $table) {
            $table->id();
            $table->string('skill');
            $table->string('strategy')->nullable();
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('replies')->default(0);
            $table->unsignedInteger('hires')->default(0);
            $table->timestamps();
            $table->unique(['skill', 'strategy']);
        });

        Schema::create('mcp_tool_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool_name');
            $table->json('arguments')->nullable();
            $table->json('result')->nullable();
            $table->string('status')->default('ok');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tables = [
            'mcp_tool_logs', 'skill_performance', 'analytics_daily', 'ai_analysis_logs', 'scheduler_runs',
            'notifications_log', 'client_messages', 'proposal_outcomes', 'proposal_ai_reviews', 'proposal_approvals',
            'proposal_portfolios', 'proposal_versions', 'proposals', 'portfolio_embeddings', 'portfolio_features',
            'portfolio_skills', 'portfolio_projects', 'job_analyses', 'job_skills', 'upwork_jobs', 'app_settings',
            'search_profiles', 'upwork_accounts',
        ];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
