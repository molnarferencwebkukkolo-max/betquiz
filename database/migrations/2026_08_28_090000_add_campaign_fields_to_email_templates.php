<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('template_type')->default('system')->after('key')->index();
            $table->unsignedInteger('days_after_registration')->nullable()->after('name');
            $table->string('header_image_path')->nullable()->after('heading');
            $table->json('content_json')->nullable()->after('body');
            $table->longText('content_html')->nullable()->after('content_json');
            $table->json('recommended_quiz_ids')->nullable()->after('content_html');
            $table->json('recommended_content_ids')->nullable()->after('recommended_quiz_ids');
            $table->boolean('include_progress')->default(false)->after('recommended_content_ids');
        });

        Schema::create('email_campaign_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->unique(['email_template_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaign_deliveries');
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['template_type', 'days_after_registration', 'header_image_path', 'content_json', 'content_html', 'recommended_quiz_ids', 'recommended_content_ids', 'include_progress']);
        });
    }
};
