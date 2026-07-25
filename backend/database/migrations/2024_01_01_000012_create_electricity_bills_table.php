<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electricity_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->date('billing_month')->comment('First day of billing month');
            $table->decimal('total_units', 10, 2);
            $table->decimal('rate_per_unit', 8, 2);
            $table->decimal('total_amount', 10, 2);
            $table->unsignedTinyInteger('active_tenants_count');
            $table->decimal('per_tenant_amount', 10, 2);
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'billing_month'], 'unique_bill_per_room_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electricity_bills');
    }
};
