<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_unit_id')->nullable(); // null = all units
            $table->enum('type', ['sms', 'email']);
            $table->string('title', 200);
            $table->string('subject', 300)->nullable(); // email only
            $table->text('body');
            $table->enum('status', ['draft', 'sending', 'sent', 'failed'])->default('draft');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
