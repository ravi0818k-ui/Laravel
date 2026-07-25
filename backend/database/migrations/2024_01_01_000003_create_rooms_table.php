<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pg_location_id')->constrained('pg_locations')->cascadeOnDelete();
            $table->string('room_number', 50);
            $table->unsignedTinyInteger('floor')->default(0);
            $table->enum('room_type', ['single', 'double', 'triple', 'quad']);
            $table->unsignedTinyInteger('total_beds')->default(1);
            $table->boolean('has_attached_bathroom')->default(false);
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_balcony')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['pg_location_id', 'room_number'], 'unique_room_per_pg');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
