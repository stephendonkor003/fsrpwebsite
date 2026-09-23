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
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->index(
                ['created_at', 'id'],
                'event_registrations_admin_timeline_index',
            );
            $table->index(
                ['event_slug', 'created_at', 'id'],
                'event_registrations_admin_event_timeline_index',
            );
            $table->index(
                'official_email_hash',
                'event_registrations_admin_email_hash_index',
            );
            $table->index(
                ['official_email_verified_at', 'created_at'],
                'event_registrations_admin_verification_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropIndex('event_registrations_admin_timeline_index');
            $table->dropIndex('event_registrations_admin_event_timeline_index');
            $table->dropIndex('event_registrations_admin_email_hash_index');
            $table->dropIndex('event_registrations_admin_verification_index');
        });
    }
};
