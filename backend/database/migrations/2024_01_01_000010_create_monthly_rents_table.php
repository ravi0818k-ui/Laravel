<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_rents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('billing_month')->comment('First day of billing month (YYYY-MM-01)');
            $table->decimal('base_rent', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('additional_charge', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2);
            $table->enum('status', ['unpaid', 'partially_paid', 'verification_pending', 'paid'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'billing_month'], 'unique_rent_per_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_rents');
    }
};
