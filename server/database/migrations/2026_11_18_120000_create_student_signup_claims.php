<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_signup_claims', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->text('student_number');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_signup_claims');
    }
};
