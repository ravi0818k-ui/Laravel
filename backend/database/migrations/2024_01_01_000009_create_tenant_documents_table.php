<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('onboarding_invitation_id')->nullable()->constrained('onboarding_invitations')->nullOnDelete();
            $table->enum('document_type', ['selfie', 'aadhaar', 'voter_id_front', 'voter_id_back', 'company_college_id', 'other']);
            $table->string('file_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 50);
            $table->unsignedInteger('file_size');
            $table->enum('verification_status', ['pending', 'verified', 'correction_required'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_documents');
    }
};
