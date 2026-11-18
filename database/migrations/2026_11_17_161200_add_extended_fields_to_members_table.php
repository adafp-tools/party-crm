<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Blind indexes for exact-match lookups (HMAC-SHA256 hex)
            $table->char('email_hash', 64)->nullable()->index()->after('email');
            $table->char('phone_hash', 64)->nullable()->index()->after('phone');

            // Extended personal data
            $table->string('address_line')->nullable()->after('phone_hash');
            $table->string('occupation')->nullable();
            $table->string('preferred_language', 10)->nullable();

            // Wings and volunteering
            $table->boolean('is_youth_wing')->default(false);
            $table->boolean('is_women_wing')->default(false);
            $table->boolean('volunteer')->default(false);
            $table->json('skills')->nullable();

            // Photo and consent
            $table->string('photo_path')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('consent_version', 50)->nullable();
            $table->enum('consent_channel', ['web', 'paper', 'phone_app'])->nullable();
            $table->boolean('sms_opt_out')->default(false);
            $table->boolean('email_opt_out')->default(false);

            // Audit / sync
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamp('client_created_at')->nullable();
            $table->unsignedInteger('sync_version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'email_hash', 'phone_hash', 'address_line', 'occupation',
                'preferred_language', 'is_youth_wing', 'is_women_wing', 'volunteer',
                'skills', 'photo_path', 'consent_at', 'consent_version',
                'consent_channel', 'sms_opt_out', 'email_opt_out',
                'created_by', 'updated_by', 'client_created_at', 'sync_version',
            ]);
        });
    }
};
