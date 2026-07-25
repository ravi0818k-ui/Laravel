<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_rent_id')->constrained('monthly_rents')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->decimal('claimed_amount', 10, 2);
            $table->decimal('verified_amount', 10, 2)->nullable();
            $table->enum('payment_method', ['upi', 'phonepe', 'gpay', 'paytm', 'bank_transfer', 'cash', 'other'])->default('upi');
            $table->string('transaction_reference')->nullable();
            $table->string('screenshot_path', 500)->nullable();
            $table->enum('status', ['submitted', 'verification_pending', 'verified', 'rejected'])->default('submitted');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_submissions');
    }
};
