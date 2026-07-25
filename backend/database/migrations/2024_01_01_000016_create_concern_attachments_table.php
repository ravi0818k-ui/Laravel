<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concern_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concern_id')->constrained('concerns')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 50);
            $table->unsignedInteger('file_size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concern_attachments');
    }
};
