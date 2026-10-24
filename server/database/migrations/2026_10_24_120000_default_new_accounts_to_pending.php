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
            DB::statement("ALTER TABLE users ALTER COLUMN account_status SET DEFAULT 'pending'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['active', 'pending', 'disabled'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN account_status SET DEFAULT 'active'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['active', 'pending', 'disabled'])
                ->default('active')
                ->change();
        });
    }
};
