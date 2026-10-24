<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->timestampTz('opens_at')->nullable()->after('display_timezone');
            $table->timestampTz('closes_at')->nullable()->after('opens_at');
            $table->unsignedInteger('whole_quiz_duration_seconds')->nullable()->after('closes_at');
            $table->unsignedInteger('per_question_duration_seconds')->nullable()->after('whole_quiz_duration_seconds');
            $table->boolean('randomize_questions')->default(false)->after('per_question_duration_seconds');
            $table->boolean('randomize_choices')->default(false)->after('randomize_questions');
        });

        Schema::create('attempt_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->unique()->constrained('quiz_attempts')->cascadeOnDelete();
            $table->string('status', 24)->default('pending');
            $table->string('scoring_version', 32)->nullable();
            $table->unsignedInteger('earned_points')->nullable();
            $table->unsignedInteger('maximum_points')->nullable();
            $table->timestampTz('scored_at')->nullable();
            $table->string('failure_code', 48)->nullable();
            $table->timestampTz('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['status', 'released_at']);
        });

        Schema::create('assessment_audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignUuid('attempt_id')->nullable()->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('event_type', 48);
            $table->timestampTz('occurred_at');
            $table->jsonb('context')->nullable();

            $table->index(['quiz_id', 'event_type', 'occurred_at']);
            $table->index(['attempt_id', 'occurred_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE quizzes
                ADD CONSTRAINT quizzes_schedule_is_ordered
                CHECK (opens_at IS NULL OR closes_at IS NULL OR opens_at < closes_at),
                ADD CONSTRAINT quizzes_positive_whole_duration
                CHECK (whole_quiz_duration_seconds IS NULL OR whole_quiz_duration_seconds > 0),
                ADD CONSTRAINT quizzes_positive_question_duration
                CHECK (per_question_duration_seconds IS NULL OR per_question_duration_seconds > 0)
                SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE attempt_results
                ADD CONSTRAINT attempt_results_points_are_valid
                CHECK (
                    (status <> 'scored' AND earned_points IS NULL AND maximum_points IS NULL)
                    OR
                    (status = 'scored' AND earned_points IS NOT NULL AND maximum_points IS NOT NULL
                        AND earned_points <= maximum_points)
                )
                SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attempt_results DROP CONSTRAINT IF EXISTS attempt_results_points_are_valid');
            DB::statement('ALTER TABLE quizzes DROP CONSTRAINT IF EXISTS quizzes_positive_question_duration');
            DB::statement('ALTER TABLE quizzes DROP CONSTRAINT IF EXISTS quizzes_positive_whole_duration');
            DB::statement('ALTER TABLE quizzes DROP CONSTRAINT IF EXISTS quizzes_schedule_is_ordered');
        }

        Schema::dropIfExists('assessment_audit_events');
        Schema::dropIfExists('attempt_results');

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'opens_at',
                'closes_at',
                'whole_quiz_duration_seconds',
                'per_question_duration_seconds',
                'randomize_questions',
                'randomize_choices',
            ]);
        });
    }
};
