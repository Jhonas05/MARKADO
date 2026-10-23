<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_revisions', function (Blueprint $table) {
            $table->timestampTz('opens_at')->nullable()->before('closes_at');
            $table->unsignedInteger('whole_quiz_duration_seconds')->nullable()->after('closes_at');
            $table->unsignedInteger('per_question_duration_seconds')->nullable()->after('whole_quiz_duration_seconds');
            $table->boolean('randomize_questions')->default(false)->after('per_question_duration_seconds');
            $table->boolean('randomize_choices')->default(false)->after('randomize_questions');
            $table->index(['status', 'opens_at', 'closes_at']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->char('permutation_seed', 64)->nullable()->after('effective_deadline_at');
            $table->unsignedSmallInteger('current_question_position')->nullable()->after('permutation_seed');
            $table->string('progress_state', 48)->default('not_started')->after('current_question_position');
            $table->unsignedBigInteger('lock_version')->default(0)->after('progress_state');
            $table->uuid('terminal_receipt_id')->nullable()->unique()->after('finalization_reason');
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->default(0)->after('selected_choice_id');
        });

        Schema::create('attempt_question_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attempt_id');
            $table->uuid('question_snapshot_id');
            $table->string('status', 24)->default('pending');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('deadline_at')->nullable();
            $table->timestampTz('locked_at')->nullable();
            $table->string('lock_reason', 32)->nullable();
            $table->timestampsTz();

            $table->foreign('attempt_id')->references('id')->on('quiz_attempts')->cascadeOnDelete();
            $table->foreign(['question_snapshot_id', 'attempt_id'])
                ->references(['id', 'attempt_id'])
                ->on('attempt_question_snapshots')
                ->cascadeOnDelete();
            $table->unique(['attempt_id', 'question_snapshot_id']);
            $table->index(['attempt_id', 'status', 'deadline_at']);
        });

        Schema::create('attempt_answer_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attempt_id');
            $table->uuid('question_snapshot_id');
            $table->string('idempotency_key', 64);
            $table->uuid('requested_choice_id')->nullable();
            $table->unsignedBigInteger('answer_version');
            $table->timestampTz('saved_at');
            $table->timestampsTz();

            $table->foreign('attempt_id')->references('id')->on('quiz_attempts')->cascadeOnDelete();
            $table->foreign(['question_snapshot_id', 'attempt_id'])
                ->references(['id', 'attempt_id'])
                ->on('attempt_question_snapshots')
                ->cascadeOnDelete();
            $table->unique(['attempt_id', 'idempotency_key']);
            $table->index(['attempt_id', 'answer_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answer_receipts');
        Schema::dropIfExists('attempt_question_states');

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique(['terminal_receipt_id']);
            $table->dropColumn([
                'permutation_seed',
                'current_question_position',
                'progress_state',
                'lock_version',
                'terminal_receipt_id',
            ]);
        });

        Schema::table('quiz_revisions', function (Blueprint $table) {
            $table->dropIndex(['status', 'opens_at', 'closes_at']);
            $table->dropColumn([
                'opens_at',
                'whole_quiz_duration_seconds',
                'per_question_duration_seconds',
                'randomize_questions',
                'randomize_choices',
            ]);
        });
    }
};
