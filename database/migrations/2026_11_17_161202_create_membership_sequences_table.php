<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_unit_id')->unique();
            $table->unsignedInteger('next_seq')->default(1);
            $table->timestamps();

            $table->foreign('org_unit_id')->references('id')->on('org_units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_sequences');
    }
};
