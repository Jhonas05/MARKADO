<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_access_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('quiz_id')->nullable()->constrained('quizzes')->nullOnDelete();
            $table->string('outcome', 40);
            $table->string('source', 32);
            $table->timestampTz('occurred_at')->useCurrent();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['quiz_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_access_events');
    }
};
