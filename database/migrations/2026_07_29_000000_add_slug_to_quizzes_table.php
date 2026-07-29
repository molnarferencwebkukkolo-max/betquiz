<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        DB::table('quizzes')
            ->select(['id', 'title'])
            ->orderBy('id')
            ->get()
            ->each(function ($quiz) {
                $baseSlug = Str::slug($quiz->title) ?: 'kviz';
                $slug = $baseSlug;
                $counter = 2;

                while (DB::table('quizzes')->where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }

                DB::table('quizzes')
                    ->where('id', $quiz->id)
                    ->update(['slug' => $slug]);
            });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
