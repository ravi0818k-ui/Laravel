<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electricity_bills', function (Blueprint $table) {
            $table->string('previous_meter_image')->nullable()->after('notes');
            $table->string('current_meter_image')->nullable()->after('previous_meter_image');
            $table->decimal('previous_reading', 10, 2)->nullable()->after('current_meter_image');
            $table->decimal('current_reading', 10, 2)->nullable()->after('previous_reading');
        });
    }

    public function down(): void
    {
        Schema::table('electricity_bills', function (Blueprint $table) {
            $table->dropColumn(['previous_meter_image', 'current_meter_image', 'previous_reading', 'current_reading']);
        });
    }
};
