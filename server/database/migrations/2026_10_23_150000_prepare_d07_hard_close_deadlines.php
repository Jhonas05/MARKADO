<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_revisions', function (Blueprint $table) {
            $table->timestampTz('closes_at')->nullable()->after('display_timezone');
            $table->index(['status', 'closes_at']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->timestampTz('quiz_closes_at')->nullable()->after('started_at');
            $table->timestampTz('session_deadline_at')->nullable()->after('quiz_closes_at');
            $table->timestampTz('effective_deadline_at')->nullable()->after('session_deadline_at');
            $table->timestampTz('finalized_at')->nullable()->after('submitted_at');
            $table->string('finalization_reason', 32)->nullable()->after('finalized_at');
            $table->index(['status', 'effective_deadline_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE quiz_attempts
                ADD CONSTRAINT quiz_attempts_effective_deadline_is_capped
                CHECK (
                    effective_deadline_at IS NULL
                    OR (
                        quiz_closes_at IS NOT NULL
                        AND effective_deadline_at = LEAST(
                            quiz_closes_at,
                            COALESCE(session_deadline_at, quiz_closes_at)
                        )
                    )
                )
                SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE quiz_attempts
                DROP CONSTRAINT IF EXISTS quiz_attempts_effective_deadline_is_capped
                SQL);
        }

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['status', 'effective_deadline_at']);
            $table->dropColumn([
                'quiz_closes_at',
                'session_deadline_at',
                'effective_deadline_at',
                'finalized_at',
                'finalization_reason',
            ]);
        });

        Schema::table('quiz_revisions', function (Blueprint $table) {
            $table->dropIndex(['status', 'closes_at']);
            $table->dropColumn('closes_at');
        });
    }
};
