<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('county', 100)->nullable();
            $table->foreignId('favorite_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('relationship_status', 20)->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->timestamp('profile_details_rewarded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('favorite_category_id');
            $table->dropColumn(['birth_date', 'gender', 'country', 'county', 'relationship_status', 'children_count', 'profile_details_rewarded_at']);
        });
    }
};
