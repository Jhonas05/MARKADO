<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE school_student_records ALTER COLUMN user_id DROP NOT NULL');
        } else {
            Schema::table('school_student_records', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }

        Schema::table('school_student_records', function (Blueprint $table) {
            $table->timestampTz('linked_at')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        if (DB::table('school_student_records')->whereNull('user_id')->exists()) {
            throw new LogicException('Cannot restore the required user link while unlinked trusted roster records exist.');
        }

        Schema::table('school_student_records', function (Blueprint $table) {
            $table->dropColumn('linked_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE school_student_records ALTER COLUMN user_id SET NOT NULL');
        } else {
            Schema::table('school_student_records', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        }
    }
};
