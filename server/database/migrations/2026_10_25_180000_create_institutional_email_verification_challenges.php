<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_email_verification_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('purpose', 64)->default('institutional_mailbox');
            $table->char('token_hash', 64)->unique();
            $table->char('email_fingerprint', 64);
            $table->timestampTz('issued_at');
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'purpose', 'issued_at'], 'email_verification_account_issued_idx');
            $table->index(
                ['user_id', 'purpose', 'consumed_at', 'revoked_at'],
                'email_verification_account_state_idx',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE institutional_email_verification_challenges
                ADD CONSTRAINT email_verification_expiry_after_issue
                CHECK (expires_at > issued_at)
            SQL);
            DB::statement(<<<'SQL'
                ALTER TABLE institutional_email_verification_challenges
                ADD CONSTRAINT email_verification_delivery_after_issue
                CHECK (delivered_at IS NULL OR delivered_at >= issued_at)
            SQL);
            DB::statement(<<<'SQL'
                ALTER TABLE institutional_email_verification_challenges
                ADD CONSTRAINT email_verification_consumed_after_delivery
                CHECK (consumed_at IS NULL OR (delivered_at IS NOT NULL AND consumed_at >= delivered_at))
            SQL);
            DB::statement(<<<'SQL'
                ALTER TABLE institutional_email_verification_challenges
                ADD CONSTRAINT email_verification_valid_terminal_state
                CHECK (
                    (revoked_at IS NULL OR revoked_at >= issued_at)
                    AND NOT (consumed_at IS NOT NULL AND revoked_at IS NOT NULL)
                )
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_email_verification_challenges');
    }
};
