<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // A production adatbázis tisztán indul. A szükséges rendszerrekordokat
        // a migrációk hozzák létre; minden demóadat külön, név szerinti seederrel
        // tölthető be, ezért a véletlen `db:seed` sem hoz létre tesztfiókot.
    }
}
