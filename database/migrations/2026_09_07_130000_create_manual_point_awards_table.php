<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_point_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('awarded_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('reason', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_point_awards');
    }
};
