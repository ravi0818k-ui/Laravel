<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('pg_location_id')->nullable()->constrained('pg_locations')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->string('candidate_name')->nullable();
            $table->string('candidate_mobile', 15)->nullable();
            $table->date('candidate_dob')->nullable();
            $table->enum('candidate_blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->string('candidate_company_college')->nullable();
            $table->text('candidate_company_college_address')->nullable();
            $table->string('candidate_parent_mobile', 15)->nullable();
            $table->string('candidate_reference_mobile_1', 15)->nullable();
            $table->string('candidate_reference_mobile_2', 15)->nullable();
            $table->foreignId('preferred_pg_location_id')->nullable()->constrained('pg_locations')->nullOnDelete();
            $table->string('referral_code_used', 30)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_invitations');
    }
};
