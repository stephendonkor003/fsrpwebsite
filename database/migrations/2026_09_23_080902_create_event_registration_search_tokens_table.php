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
        Schema::create('event_registration_search_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_registration_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('namespace', 40);
            $table->char('token_hash', 64);

            $table->unique(
                ['event_registration_id', 'namespace', 'token_hash'],
                'event_registration_tokens_owner_ns_hash_unique',
            );
            $table->index(
                ['namespace', 'token_hash', 'event_registration_id'],
                'event_registration_tokens_lookup_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registration_search_tokens');
    }
};
