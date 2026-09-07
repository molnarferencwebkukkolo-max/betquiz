<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_point_awards', function (Blueprint $table) {
            $table->integer('amount')->change();
        });
    }

    public function down(): void
    {
        Schema::table('manual_point_awards', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });
    }
};
