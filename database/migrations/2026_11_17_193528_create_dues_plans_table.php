<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dues_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10);
            $table->enum('period', ['monthly', 'yearly', 'one_off']);
            $table->enum('applies_to', ['all', 'member', 'supporter', 'youth_wing', 'women_wing'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dues_plans');
    }
};
