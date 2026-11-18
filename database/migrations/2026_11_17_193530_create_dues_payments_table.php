<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dues_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('dues_plan_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10);
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->enum('method', ['cash', 'telebirr', 'wave', 'orange_money', 'bank', 'other']);
            $table->string('reference', 100)->nullable();
            $table->string('receipt_no', 100)->nullable();
            $table->enum('status', ['recorded', 'confirmed', 'reconciled', 'rejected'])->default('recorded');
            $table->string('rejection_reason')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->uuid('confirmed_by')->nullable();
            $table->uuid('reconciled_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->foreign('dues_plan_id')->references('id')->on('dues_plans')->nullOnDelete();

            $table->index(['member_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('period_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dues_payments');
    }
};
