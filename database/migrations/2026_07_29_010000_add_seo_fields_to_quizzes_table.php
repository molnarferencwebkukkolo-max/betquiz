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
            $table->string('seo_title')->nullable()->after('slug');
            $table->string('seo_description', 160)->nullable()->after('seo_title');
        });

        DB::table('quizzes')
            ->select(['id', 'title', 'description'])
            ->orderBy('id')
            ->get()
            ->each(function ($quiz) {
                DB::table('quizzes')
                    ->where('id', $quiz->id)
                    ->update([
                        'seo_title' => $quiz->title,
                        'seo_description' => Str::limit(strip_tags((string) $quiz->description), 160, ''),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description']);
        });
    }
};
