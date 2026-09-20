<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_analyses', function (Blueprint $table) {
            $table->longText('thoughts')->nullable()->after('summary');
            $table->string('recommendation')->nullable()->after('thoughts'); // pursue|maybe|skip
            $table->text('win_strategy')->nullable()->after('recommendation');
            $table->json('must_haves')->nullable()->after('win_strategy');
            $table->json('nice_to_haves')->nullable()->after('must_haves');
            $table->json('red_flags')->nullable()->after('nice_to_haves');
        });

        Schema::create('job_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_job_id')->constrained('upwork_jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role'); // user|assistant|system
            $table->longText('content');
            $table->json('meta')->nullable(); // e.g. proposal_draft, improved_letter
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_chat_messages');
        Schema::table('job_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'thoughts', 'recommendation', 'win_strategy',
                'must_haves', 'nice_to_haves', 'red_flags',
            ]);
        });
    }
};
