<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('description', 500)->nullable()->after('icon');
        });

        // A korábbi részkategóriák tartalmát a megfelelő új
        // főkategóriába vezetjük át, adatvesztés nélkül.
        $scienceId = DB::table('categories')->where('slug', 'tudomany')->value('id');
        if ($scienceId) {
            $mergedIds = DB::table('categories')->whereIn('slug', ['csillagaszat', 'kemia'])->pluck('id');
            DB::table('quizzes')->whereIn('category_id', $mergedIds)->update(['category_id' => $scienceId]);
            DB::table('questions')->whereIn('category_id', $mergedIds)->update(['category_id' => $scienceId]);
            DB::table('categories')->whereIn('id', $mergedIds)->delete();
        }

        $movies = DB::table('categories')->where('slug', 'filmek')->first();
        if ($movies) {
            DB::table('categories')->where('id', $movies->id)->update(['slug' => 'film-es-televizio']);
        }

        $categories = [
            ['slug' => 'foldrajz', 'name' => 'Földrajz', 'icon' => '🌍', 'description' => 'Országok, városok, folyók, hegyek, zászlók'],
            ['slug' => 'tortenelem', 'name' => 'Történelem', 'icon' => '📜', 'description' => 'Ókor, középkor, világháborúk, magyar történelem'],
            ['slug' => 'tudomany', 'name' => 'Tudomány', 'icon' => '🔬', 'description' => 'Fizika, kémia, biológia, csillagászat'],
            ['slug' => 'matematika-es-logika', 'name' => 'Matematika és logika', 'icon' => '➗', 'description' => 'Számolás, logikai feladatok'],
            ['slug' => 'informatika-es-technologia', 'name' => 'Informatika és technológia', 'icon' => '🖥', 'description' => 'Számítógépek, internet, AI, programozás'],
            ['slug' => 'sport', 'name' => 'Sport', 'icon' => '⚽', 'description' => 'Minden sportág'],
            ['slug' => 'irodalom', 'name' => 'Irodalom', 'icon' => '📚', 'description' => 'Könyvek, írók, költők'],
            ['slug' => 'muveszetek', 'name' => 'Művészetek', 'icon' => '🎭', 'description' => 'Festészet, szobrászat, építészet, fotó'],
            ['slug' => 'zene', 'name' => 'Zene', 'icon' => '🎵', 'description' => 'Előadók, zenetörténet, hangszerek'],
            ['slug' => 'film-es-televizio', 'name' => 'Film és televízió', 'icon' => '🎬', 'description' => 'Filmek, sorozatok, színészek'],
            ['slug' => 'jatekok', 'name' => 'Játékok', 'icon' => '🎮', 'description' => 'Videojátékok, társasjátékok'],
            ['slug' => 'hires-emberek', 'name' => 'Híres emberek', 'icon' => '👤', 'description' => 'Tudósok, politikusok, sportolók, influenszerek'],
            ['slug' => 'termeszet', 'name' => 'Természet', 'icon' => '🌿', 'description' => 'Állatok, növények, környezet'],
            ['slug' => 'kultura-es-vallas', 'name' => 'Kultúra és vallás', 'icon' => '🏛', 'description' => 'Népszokások, mitológia, vallások'],
            ['slug' => 'gasztronomia', 'name' => 'Gasztronómia', 'icon' => '🍽', 'description' => 'Ételek, italok, alapanyagok'],
            ['slug' => 'kozlekedes', 'name' => 'Közlekedés', 'icon' => '🚗', 'description' => 'Autók, vasút, repülés, hajózás'],
            ['slug' => 'gazdasag-es-uzlet', 'name' => 'Gazdaság és üzlet', 'icon' => '💼', 'description' => 'Pénzügy, vállalatok, márkák'],
            ['slug' => 'tarsadalom', 'name' => 'Társadalom', 'icon' => '⚖', 'description' => 'Jog, politika, oktatás, média'],
            ['slug' => 'eletmod', 'name' => 'Életmód', 'icon' => '🏠', 'description' => 'Egészség, divat, otthon, hobbi'],
            ['slug' => 'szorakozas', 'name' => 'Szórakozás', 'icon' => '🎉', 'description' => 'Bulvár, celebek, rekordok, érdekességek'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'name' => json_encode(['hu' => $category['name']], JSON_UNESCAPED_UNICODE),
                    'icon' => $category['icon'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('categories')->whereNotIn('slug', array_column($categories, 'slug'))->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('description'));
    }
};
