<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electricity_bill_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electricity_bill_id')->constrained('electricity_bills')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['electricity_bill_id', 'tenant_id'], 'unique_allocation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electricity_bill_allocations');
    }
};
