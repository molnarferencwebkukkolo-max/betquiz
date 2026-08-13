<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // A name mezőt kompatibilitási okból megtartjuk, de nem kezelünk
        // külön megjelenítési nevet: mindig a username másolata.
        DB::table('users')
            ->whereNotNull('username')
            ->update(['name' => DB::raw('username')]);
    }

    public function down(): void
    {
        // A korábbi, külön megjelenítési nevek nem állíthatók vissza.
    }
};
