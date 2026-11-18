<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_unit_id');
            $table->string('title');
            $table->enum('type', ['meeting', 'rally', 'training', 'congress', 'other'])->default('meeting');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location_text', 500)->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('org_unit_id')->references('id')->on('org_units');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index('org_unit_id');
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
