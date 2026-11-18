<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->uuid('org_level_id');
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('path', 2000)->default('/');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('org_units')->nullOnDelete();
            $table->foreign('org_level_id')->references('id')->on('org_levels');
            $table->index('path');
            $table->index('code');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_units');
    }
};
