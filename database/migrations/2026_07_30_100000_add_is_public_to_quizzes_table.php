<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('status')->index();
        });

        // A korábban já játszható kvízek láthatósága ne változzon.
        DB::table('quizzes')
            ->where('status', 'approved')
            ->update(['is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
