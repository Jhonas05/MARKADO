<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_student_records', function (Blueprint $table) {
            $table->string('roster_source', 120)->nullable()->after('institutional_email');
            $table->timestampTz('roster_synced_at')->nullable()->after('roster_source');
            $table->index(['roster_source', 'roster_synced_at']);
        });
    }

    public function down(): void
    {
        Schema::table('school_student_records', function (Blueprint $table) {
            $table->dropIndex(['roster_source', 'roster_synced_at']);
            $table->dropColumn(['roster_source', 'roster_synced_at']);
        });
    }
};
