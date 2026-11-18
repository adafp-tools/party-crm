<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->unsignedBigInteger('role_id');
            $table->uuid('org_unit_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->nullOnDelete();
            $table->unique(['user_id', 'role_id', 'org_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
    }
};
