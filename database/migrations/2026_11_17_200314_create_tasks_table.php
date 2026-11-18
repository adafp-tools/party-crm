<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_unit_id');
            $table->string('title');
            $table->text('details')->nullable();
            $table->uuid('assignee_id')->nullable();
            $table->uuid('related_member_id')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'done', 'cancelled'])->default('pending');
            $table->boolean('digest_opt_out')->default(false);
            $table->timestamp('digest_sent_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('org_unit_id')->references('id')->on('org_units');
            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('related_member_id')->references('id')->on('members')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['assignee_id', 'status']);
            $table->index(['org_unit_id', 'status']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
