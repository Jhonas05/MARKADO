<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_student_records', function (Blueprint $table) {
            $table->string('institutional_email', 254)->nullable()->unique()->after('student_number');
        });

        Schema::create('student_identity_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('outcome', 64);
            $table->timestampTz('occurred_at');

            $table->index(['user_id', 'occurred_at']);
            $table->index(['outcome', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_identity_events');

        Schema::table('school_student_records', function (Blueprint $table) {
            $table->dropUnique(['institutional_email']);
            $table->dropColumn('institutional_email');
        });
    }
};
