<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('tenant_id', 20)->unique();
            $table->foreignId('pg_location_id')->constrained('pg_locations')->restrictOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->string('company_or_college')->nullable();
            $table->text('company_college_address')->nullable();
            $table->string('parent_mobile', 15)->nullable();
            $table->string('reference_mobile_1', 15)->nullable();
            $table->string('reference_mobile_2', 15)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_mobile', 15)->nullable();
            $table->string('referral_code', 30)->unique();
            $table->string('referred_by_code', 30)->nullable();
            $table->date('joining_date');
            $table->decimal('current_rent', 10, 2);
            $table->decimal('security_deposit', 10, 2)->nullable()->default(0);
            $table->enum('status', ['active', 'offboarded', 'suspended'])->default('active');
            $table->timestamp('offboarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
