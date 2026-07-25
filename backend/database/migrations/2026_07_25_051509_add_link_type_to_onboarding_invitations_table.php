<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_invitations', function (Blueprint $table) {
            $table->enum('link_type', ['bulk', 'single'])->default('bulk')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_invitations', function (Blueprint $table) {
            $table->dropColumn('link_type');
        });
    }
};
