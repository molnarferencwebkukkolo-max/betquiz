<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A tiltást és az inaktiválást külön kezeljük: a tiltás későbbi
     * funkciókorlátozások alapja, az inaktív fiók pedig nem léphet be.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('role')->index();
            $table->boolean('is_active')->default(true)->after('is_banned')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_banned', 'is_active']);
        });
    }
};
