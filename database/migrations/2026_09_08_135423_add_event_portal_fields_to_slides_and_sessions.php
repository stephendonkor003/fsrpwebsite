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
        Schema::table('slides', function (Blueprint $table): void {
            $table->string('video_url', 2048)->nullable();
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->boolean('is_all_day')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slides', function (Blueprint $table): void {
            $table->dropColumn('video_url');
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->dropColumn('is_all_day');
        });
    }
};
