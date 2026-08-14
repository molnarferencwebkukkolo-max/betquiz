<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('format', 20);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->string('image_path')->nullable();
            $table->string('target_url', 2048)->nullable();
            $table->string('alt_text')->nullable();
            $table->text('adsense_code')->nullable();
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('advertisement_placement', function (Blueprint $table) {
            $table->foreignId('advertisement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_placement_id')->constrained()->cascadeOnDelete();
            $table->primary(['advertisement_id', 'ad_placement_id']);
        });

        // A fix kulcsok miatt a Blade komponensek minden környezetben ugyanazokat
        // a logikai hirdetési helyeket tudják megszólítani.
        $now = now();
        DB::table('ad_placements')->insert([
            ['key' => 'top_horizontal', 'name' => 'Felső vízszintes banner', 'format' => 'horizontal', 'description' => 'A navigáció vagy kiemelt fejléc alatti széles hirdetési hely.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'content_horizontal', 'name' => 'Tartalmi vízszintes banner', 'format' => 'horizontal', 'description' => 'Természetes tartalmi blokkok közötti széles hirdetési hely.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'right_sidebar', 'name' => 'Jobb oldalsó banner', 'format' => 'sidebar', 'description' => 'Asztali nézetben a fő tartalom jobb oldalán megjelenő hirdetés.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisement_placement');
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('ad_placements');
    }
};
