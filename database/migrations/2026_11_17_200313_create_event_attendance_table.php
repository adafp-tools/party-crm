<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->uuid('member_id');
            $table->enum('status', ['invited', 'attended', 'absent'])->default('invited');
            $table->string('client_uuid', 100)->nullable()->unique();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();

            $table->unique(['event_id', 'member_id']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendance');
    }
};
