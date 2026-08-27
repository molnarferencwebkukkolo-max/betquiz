<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // A mar mukodo fiokokat nem zarjuk ki visszamenolegesen az uj szaballyal.
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // A korabbi hitelesitesi allapot nem allithato vissza adatvesztes nelkul.
    }
};
