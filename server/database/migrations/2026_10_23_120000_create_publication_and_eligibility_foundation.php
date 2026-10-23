<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->uuid('source_revision_id')->nullable();
            $table->unsignedInteger('revision_number');
            $table->string('status', 24)->default('draft');
            $table->string('title', 160);
            $table->string('course_code', 32);
            $table->string('course_name', 120)->nullable();
            $table->text('instructions')->nullable();
            $table->string('display_timezone', 64)->default('Asia/Manila');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();

            $table->unique(['quiz_id', 'revision_number']);
            $table->index(['quiz_id', 'status']);
        });

        Schema::table('quiz_revisions', function (Blueprint $table) {
            $table->foreign('source_revision_id')->references('id')->on('quiz_revisions')->nullOnDelete();
        });

        Schema::create('quiz_revision_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_revision_id')->constrained('quiz_revisions')->cascadeOnDelete();
            $table->foreignUuid('source_question_id')->nullable()->constrained('quiz_questions')->nullOnDelete();
            $table->string('type', 32);
            $table->text('prompt');
            $table->unsignedSmallInteger('position');
            $table->timestampsTz();

            $table->unique(['quiz_revision_id', 'position']);
            $table->index(['quiz_revision_id', 'type']);
        });

        Schema::create('quiz_revision_choices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('revision_question_id')->constrained('quiz_revision_questions')->cascadeOnDelete();
            $table->foreignUuid('source_choice_id')->nullable()->constrained('quiz_choices')->nullOnDelete();
            $table->text('choice_text');
            $table->unsignedSmallInteger('position');
            $table->boolean('is_correct')->default(false);
            $table->timestampsTz();

            $table->unique(['revision_question_id', 'position']);
        });

        Schema::create('school_student_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('student_number', 64)->unique();
            $table->string('verification_status', 24)->default('unverified');
            $table->boolean('is_active')->default(true);
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampsTz();

            $table->index(['verification_status', 'is_active']);
        });

        Schema::create('school_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name', 160);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
        });

        Schema::create('school_section_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_id')->constrained('school_sections')->cascadeOnDelete();
            $table->foreignUuid('student_record_id')->constrained('school_student_records')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['section_id', 'student_record_id']);
        });

        Schema::create('quiz_student_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignUuid('student_record_id')->constrained('school_student_records')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['quiz_id', 'student_record_id']);
        });

        Schema::create('quiz_section_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignUuid('section_id')->constrained('school_sections')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['quiz_id', 'section_id']);
        });

        Schema::create('quiz_publication_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignUuid('quiz_revision_id')->nullable()->constrained('quiz_revisions')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('event_type', 32);
            $table->timestampTz('occurred_at');

            $table->index(['quiz_id', 'occurred_at']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignUuid('current_published_revision_id')->nullable()->after('status')
                ->constrained('quiz_revisions')->restrictOnDelete();
            $table->foreignUuid('active_draft_revision_id')->nullable()->after('current_published_revision_id')
                ->constrained('quiz_revisions')->restrictOnDelete();
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignUuid('quiz_revision_id')->nullable()->after('quiz_id')
                ->constrained('quiz_revisions')->restrictOnDelete();
        });

        Schema::table('attempt_question_snapshots', function (Blueprint $table) {
            $table->foreignUuid('source_revision_question_id')->nullable()->after('source_question_id')
                ->constrained('quiz_revision_questions')->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION markado_guard_published_quiz_revision()
                RETURNS trigger AS $$
                BEGIN
                    IF OLD.status = 'published' THEN
                        RAISE EXCEPTION 'Published quiz revisions are immutable'
                            USING ERRCODE = '55000';
                    END IF;
                    IF TG_OP = 'DELETE' THEN
                        RETURN OLD;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER guard_published_quiz_revision
                BEFORE UPDATE OR DELETE ON quiz_revisions
                FOR EACH ROW EXECUTE FUNCTION markado_guard_published_quiz_revision();

                CREATE OR REPLACE FUNCTION markado_guard_published_revision_content()
                RETURNS trigger AS $$
                DECLARE
                    target_revision_id uuid;
                    target_status varchar(24);
                BEGIN
                    IF TG_TABLE_NAME = 'quiz_revision_questions' THEN
                        target_revision_id := COALESCE(NEW.quiz_revision_id, OLD.quiz_revision_id);
                    ELSE
                        SELECT quiz_revision_id INTO target_revision_id
                        FROM quiz_revision_questions
                        WHERE id = COALESCE(NEW.revision_question_id, OLD.revision_question_id);
                    END IF;

                    SELECT status INTO target_status
                    FROM quiz_revisions
                    WHERE id = target_revision_id;

                    IF target_status = 'published' THEN
                        RAISE EXCEPTION 'Published quiz revision content is immutable'
                            USING ERRCODE = '55000';
                    END IF;
                    IF TG_OP = 'DELETE' THEN
                        RETURN OLD;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER guard_published_revision_questions
                BEFORE INSERT OR UPDATE OR DELETE ON quiz_revision_questions
                FOR EACH ROW EXECUTE FUNCTION markado_guard_published_revision_content();

                CREATE TRIGGER guard_published_revision_choices
                BEFORE INSERT OR UPDATE OR DELETE ON quiz_revision_choices
                FOR EACH ROW EXECUTE FUNCTION markado_guard_published_revision_content();
                SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS guard_published_revision_choices ON quiz_revision_choices;
                DROP TRIGGER IF EXISTS guard_published_revision_questions ON quiz_revision_questions;
                DROP TRIGGER IF EXISTS guard_published_quiz_revision ON quiz_revisions;
                DROP FUNCTION IF EXISTS markado_guard_published_revision_content();
                DROP FUNCTION IF EXISTS markado_guard_published_quiz_revision();
                SQL);
        }

        Schema::table('attempt_question_snapshots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_revision_question_id');
        });
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quiz_revision_id');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_draft_revision_id');
            $table->dropConstrainedForeignId('current_published_revision_id');
        });

        Schema::dropIfExists('quiz_publication_events');
        Schema::dropIfExists('quiz_section_assignments');
        Schema::dropIfExists('quiz_student_assignments');
        Schema::dropIfExists('school_section_memberships');
        Schema::dropIfExists('school_sections');
        Schema::dropIfExists('school_student_records');
        Schema::dropIfExists('quiz_revision_choices');
        Schema::dropIfExists('quiz_revision_questions');
        Schema::dropIfExists('quiz_revisions');
    }
};
