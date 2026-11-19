<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedBigInteger('token_id')->nullable()->after('platform');
            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('last_synced_at');
            $table->timestamp('wipe_requested_at')->nullable()->after('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['token_id']);
            $table->dropColumn(['token_id', 'revoked_at', 'wipe_requested_at']);
        });
    }
};
