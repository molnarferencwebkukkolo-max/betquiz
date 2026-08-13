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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
        });

        // A meglévő fiókok kapnak stabil, egyedi felhasználónevet, így csak az
        // első Google-belépéssel létrejött fióknak kell onboardingot mutatni.
        DB::table('users')->orderBy('id')->get(['id', 'name'])->each(function ($user): void {
            $base = Str::lower(Str::slug((string) $user->name, '_')) ?: 'user'.$user->id;
            $base = Str::limit($base, 24, '');
            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = Str::limit($base, 24, '').'_'.$suffix++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropUnique(['username']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('username'));
    }
};
