<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->string('heading');
            $table->text('body');
            $table->string('button_text')->nullable();
            $table->text('footer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('email_templates')->insert([
            ['key' => 'email_verification', 'name' => 'E-mail-cim hitelesitese', 'subject' => 'KwizzGo - e-mail-cim hitelesitese', 'heading' => 'Szia {{username}}!', 'body' => "Koszonjuk a regisztraciot!\nAz e-mail-cimed hitelesitesehez kattints az alabbi gombra.", 'button_text' => 'E-mail-cim hitelesitese', 'footer' => 'Ha nem te regisztraltal, nincs tovabbi teendod.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'welcome', 'name' => 'Welcome level', 'subject' => 'Udvozol a KwizzGo!', 'heading' => 'Szia {{username}}!', 'body' => "Sikeresen hitelesitetted az e-mail-cimed.\nMostantol elered a KwizzGo kvizeit, pontgyujto lehetosegeit es kozossegi funkcioit.", 'button_text' => 'Irány a KwizzGo', 'footer' => 'Jo jatekot kivan a KwizzGo csapata!', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
