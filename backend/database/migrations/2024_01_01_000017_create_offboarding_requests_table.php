<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('initiated_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->date('expected_leaving_date');
            $table->date('actual_leaving_date')->nullable();
            $table->text('feedback')->nullable();
            $table->decimal('outstanding_rent', 10, 2)->default(0);
            $table->decimal('outstanding_electricity', 10, 2)->default(0);
            $table->decimal('security_deposit_refund', 10, 2)->nullable();
            $table->enum('status', ['requested', 'pending_dues', 'approved', 'completed', 'cancelled'])->default('requested');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_requests');
    }
};
