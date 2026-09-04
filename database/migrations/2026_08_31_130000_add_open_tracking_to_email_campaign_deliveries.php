<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_campaign_deliveries', function (Blueprint $table) {
            $table->uuid('tracking_token')->nullable()->unique()->after('user_id');
            $table->timestamp('opened_at')->nullable()->after('sent_at');
        });
        DB::table('email_campaign_deliveries')->orderBy('id')->eachById(fn ($delivery) =>
            DB::table('email_campaign_deliveries')->where('id', $delivery->id)->update(['tracking_token' => (string) Str::uuid()]));
    }
    public function down(): void
    {
        Schema::table('email_campaign_deliveries', function (Blueprint $table) {
            $table->dropUnique(['tracking_token']);
            $table->dropColumn(['tracking_token', 'opened_at']);
        });
    }
};
