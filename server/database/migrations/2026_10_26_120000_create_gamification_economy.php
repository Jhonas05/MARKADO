<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 48)->unique();
            $table->string('name', 80);
            $table->string('description', 240);
            $table->unsignedInteger('price');
            $table->string('artwork_path', 160);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('student_characters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('character_id')->constrained('characters')->restrictOnDelete();
            $table->string('acquisition_type', 32);
            $table->unsignedInteger('price_paid')->default(0);
            $table->timestampTz('acquired_at');
            $table->timestampsTz();

            $table->unique(['student_id', 'character_id']);
            $table->index(['student_id', 'acquired_at']);
        });

        Schema::create('student_wallets', function (Blueprint $table) {
            $table->foreignId('student_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->uuid('equipped_character_id')->nullable();
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestampsTz();

            $table->foreign('equipped_character_id')->references('id')->on('characters')->restrictOnDelete();
            $table->foreign(['student_id', 'equipped_character_id'])
                ->references(['student_id', 'character_id'])
                ->on('student_characters')
                ->restrictOnDelete();
        });

        Schema::create('coin_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('entry_type', 40);
            $table->bigInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->foreignUuid('attempt_id')->nullable()->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignUuid('character_id')->nullable()->constrained('characters')->restrictOnDelete();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['student_id', 'occurred_at']);
        });

        Schema::create('attempt_coin_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->unique()->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('ledger_entry_id')->unique()->constrained('coin_ledger_entries')->cascadeOnDelete();
            $table->unsignedInteger('coins');
            $table->timestampTz('awarded_at');
            $table->timestampsTz();

            $table->index(['student_id', 'awarded_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE characters ADD CONSTRAINT characters_price_is_nonnegative CHECK (price >= 0)');
            DB::statement('ALTER TABLE student_wallets ADD CONSTRAINT student_wallets_balance_is_nonnegative CHECK (balance >= 0)');
            DB::statement('ALTER TABLE student_characters ADD CONSTRAINT student_characters_price_paid_is_nonnegative CHECK (price_paid >= 0)');
            DB::statement('ALTER TABLE coin_ledger_entries ADD CONSTRAINT coin_ledger_balance_is_nonnegative CHECK (balance_after >= 0)');
            DB::statement(<<<'SQL'
                ALTER TABLE coin_ledger_entries
                ADD CONSTRAINT coin_ledger_entry_shape_is_valid
                CHECK (
                    (entry_type = 'quiz_completion_reward' AND amount > 0 AND attempt_id IS NOT NULL AND character_id IS NULL)
                    OR
                    (entry_type = 'character_purchase' AND amount <= 0 AND attempt_id IS NULL AND character_id IS NOT NULL)
                )
                SQL);
            DB::statement('ALTER TABLE attempt_coin_rewards ADD CONSTRAINT attempt_coin_rewards_coins_are_positive CHECK (coins > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_coin_rewards');
        Schema::dropIfExists('coin_ledger_entries');
        Schema::dropIfExists('student_wallets');
        Schema::dropIfExists('student_characters');
        Schema::dropIfExists('characters');
    }
};
