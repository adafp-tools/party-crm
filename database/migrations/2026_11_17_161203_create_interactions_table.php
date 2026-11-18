<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->enum('type', ['call', 'meeting', 'visit', 'sms', 'email', 'other']);
            $table->string('summary')->nullable();
            $table->timestamp('occurred_at');
            $table->uuid('by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
