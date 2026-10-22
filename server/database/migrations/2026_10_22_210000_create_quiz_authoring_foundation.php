<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 160);
            $table->string('course_code', 32);
            $table->string('course_name', 120)->nullable();
            $table->text('instructions')->nullable();
            $table->string('status', 24)->default('draft');
            $table->string('access_code', 12)->unique();
            $table->string('display_timezone', 64)->default('Asia/Manila');
            $table->timestampsTz();

            $table->index(['owner_id', 'status']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('type', 32);
            $table->text('prompt');
            $table->unsignedSmallInteger('position');
            $table->timestampsTz();

            $table->unique(['quiz_id', 'position']);
            $table->index(['quiz_id', 'type']);
        });

        Schema::create('quiz_choices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->text('choice_text');
            $table->unsignedSmallInteger('position');
            $table->boolean('is_correct')->default(false);
            $table->timestampsTz();

            $table->unique(['question_id', 'position']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quizzes')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('in_progress');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('last_saved_at')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('result_released_at')->nullable();
            $table->foreignId('result_released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['quiz_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('attempt_question_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignUuid('source_question_id')->nullable()->constrained('quiz_questions')->nullOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('type', 32);
            $table->text('prompt');
            $table->jsonb('choices');
            $table->uuid('correct_choice_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['attempt_id', 'position']);
            $table->unique(['attempt_id', 'source_question_id']);
            $table->unique(['id', 'attempt_id']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attempt_id');
            $table->uuid('question_snapshot_id');
            $table->uuid('selected_choice_id')->nullable();
            $table->timestampTz('saved_at')->nullable();
            $table->timestampsTz();

            $table->foreign('attempt_id')->references('id')->on('quiz_attempts')->cascadeOnDelete();
            $table->foreign(['question_snapshot_id', 'attempt_id'])
                ->references(['id', 'attempt_id'])
                ->on('attempt_question_snapshots')
                ->cascadeOnDelete();
            $table->unique(['attempt_id', 'question_snapshot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('attempt_question_snapshots');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_choices');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
