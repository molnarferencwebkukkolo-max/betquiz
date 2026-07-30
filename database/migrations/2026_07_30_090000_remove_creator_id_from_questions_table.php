<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A kérdés tulajdonosa mindig a hozzá tartozó kvíz tulajdonosa.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creator_id');
        });
    }

    /**
     * Visszaállítási lehetőség a korábbi, redundáns adatmodellhez.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }
};
