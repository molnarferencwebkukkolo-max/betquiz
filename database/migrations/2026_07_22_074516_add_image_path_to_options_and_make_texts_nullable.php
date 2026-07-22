<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('question_text')->nullable()->change();
        });

        Schema::table('options', function (Blueprint $table) {
            $table->text('option_text')->nullable()->change();

            // Csak akkor adjuk hozzá az image_path oszlopot, ha még nem létezik
            if (!Schema::hasColumn('options', 'image_path')) {
                $table->string('image_path')->nullable()->after('option_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('question_text')->nullable(false)->change();
        });

        Schema::table('options', function (Blueprint $table) {
            $table->text('option_text')->nullable(false)->change();

            if (Schema::hasColumn('options', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
