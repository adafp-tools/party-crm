<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_unit_id');
            $table->string('membership_no', 50)->nullable()->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->enum('category', ['member', 'supporter'])->default('member');
            $table->enum('status', [
                'applicant',
                'active',
                'lapsed',
                'suspended',
                'expelled',
                'deceased',
                'resigned',
            ])->default('applicant');
            $table->date('joined_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('org_unit_id')->references('id')->on('org_units');
            $table->index('status');
            $table->index('org_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
