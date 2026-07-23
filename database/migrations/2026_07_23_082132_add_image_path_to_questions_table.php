<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Csak akkor adja hozzá, ha MÉG NEM LÉTEZIK!
            if (!Schema::hasColumn('questions', 'image_path')) {
                $table->string('image_path')->nullable()->after('question_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
