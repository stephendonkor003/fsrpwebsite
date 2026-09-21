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
            $table->string('receipt_email_status', 20)
                ->default('pending')
                ->after('confirmation_email_failed_at');
            $table->timestamp('receipt_email_queued_at')
                ->nullable()
                ->after('receipt_email_status');
            $table->timestamp('receipt_email_sent_at')
                ->nullable()
                ->after('receipt_email_queued_at');
            $table->timestamp('receipt_email_failed_at')
                ->nullable()
                ->after('receipt_email_sent_at');

            $table->index(['receipt_email_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropIndex(['receipt_email_status', 'created_at']);
            $table->dropColumn([
                'receipt_email_status',
                'receipt_email_queued_at',
                'receipt_email_sent_at',
                'receipt_email_failed_at',
            ]);
        });
    }
};
