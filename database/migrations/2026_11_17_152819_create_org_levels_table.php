<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('depth')->default(0);
            $table->string('name_key', 100);
            $table->boolean('code_required')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_levels');
    }
};
