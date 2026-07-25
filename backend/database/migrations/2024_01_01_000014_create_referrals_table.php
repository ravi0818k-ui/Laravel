<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('referred_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('onboarding_invitation_id')->nullable()->constrained('onboarding_invitations')->nullOnDelete();
            $table->string('referral_code_used', 30);
            $table->enum('status', ['pending', 'converted', 'expired'])->default('pending');
            $table->string('reward_type', 50)->nullable();
            $table->decimal('reward_amount', 10, 2)->nullable();
            $table->boolean('reward_applied')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
