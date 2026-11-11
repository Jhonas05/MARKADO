<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosted_presentation_environment_guards', function (Blueprint $table): void {
            $table->string('guard_key', 64)->primary();
            $table->string('environment_id', 64)->unique();
            $table->string('environment_name', 64);
            $table->string('app_url', 255);
            $table->string('database_name', 128);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosted_presentation_environment_guards');
    }
};
