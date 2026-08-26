<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A célzás külön kapcsolótáblákon marad, így egy kreatív több
        // kategóriához és több konkrét kvízhez is biztonságosan rendelhető.
        Schema::create('advertisement_category', function (Blueprint $table) {
            $table->foreignId('advertisement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['advertisement_id', 'category_id']);
        });

        Schema::create('advertisement_quiz', function (Blueprint $table) {
            $table->foreignId('advertisement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->primary(['advertisement_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisement_quiz');
        Schema::dropIfExists('advertisement_category');
    }
};
