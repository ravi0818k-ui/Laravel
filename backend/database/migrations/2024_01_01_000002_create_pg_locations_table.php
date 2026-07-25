<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pg_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->string('city', 100);
            $table->string('state', 100)->default('Haryana');
            $table->string('pincode', 10);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('tenant_id_prefix', 10)->unique();
            $table->unsignedInteger('tenant_id_counter')->default(0);
            $table->string('contact_mobile', 15)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('description')->nullable();
            $table->json('photos')->nullable();
            $table->json('metadata')->nullable()->comment('Frontend-specific: slug, sharing_type, whatsapp, map_iframe, map_link, videos, amenities, meals, tags, security_deposit, phone_display');
            $table->decimal('starting_rent', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_locations');
    }
};
