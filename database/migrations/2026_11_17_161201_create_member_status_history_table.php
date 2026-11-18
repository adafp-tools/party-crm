<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('reason')->nullable();
            $table->uuid('changed_by')->nullable();
            $table->timestamp('changed_at');

            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_status_history');
    }
};
