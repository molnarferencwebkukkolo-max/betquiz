<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_quiz_helper_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('helper', 30);
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->unsignedBigInteger('question_id')->nullable();
            $table->boolean('was_free');
            $table->unsignedInteger('points_spent')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'helper']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quiz_helper_usages');
    }
};
