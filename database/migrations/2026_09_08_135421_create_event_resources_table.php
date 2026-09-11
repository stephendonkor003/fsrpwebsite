<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('category', 30);
            $table->string('language', 2)->default('en');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_published')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
            $table->index(['event_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_resources');
    }
};
