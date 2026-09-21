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
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_slug');
            $table->string('event_title');
            $table->string('event_venue')->nullable();
            $table->dateTime('event_start_at')->nullable();
            $table->dateTime('event_end_at')->nullable();
            $table->string('locale', 5)->default('en');

            $table->text('title')->nullable();
            $table->text('first_name');
            $table->text('surname');
            $table->text('gender')->nullable();
            $table->text('date_of_birth')->nullable();
            $table->text('nationality')->nullable();

            $table->text('national_id_number')->nullable();
            $table->text('passport_number');
            $table->text('passport_expiry_date')->nullable();
            $table->text('issuing_country')->nullable();
            $table->boolean('visa_required')->nullable();
            $table->string('passport_photo_path')->nullable();
            $table->text('passport_photo_original_name')->nullable();
            $table->string('passport_scan_path')->nullable();
            $table->text('passport_scan_original_name')->nullable();

            $table->text('organisation');
            $table->text('member_state')->nullable();
            $table->text('delegation_capacity')->nullable();
            $table->unsignedSmallInteger('years_in_service')->nullable();
            $table->text('areas_of_expertise')->nullable();

            $table->text('mobile_number');
            $table->text('alternative_phone')->nullable();
            $table->text('official_email');
            $table->char('official_email_hash', 64);
            $table->char('verified_email_hash', 64)->nullable();
            $table->text('personal_email')->nullable();
            $table->text('emergency_contact')->nullable();

            $table->text('arrival_date')->nullable();
            $table->text('departure_date')->nullable();
            $table->text('dietary_requirements')->nullable();
            $table->text('other_dietary_needs')->nullable();
            $table->boolean('dinner_attendance')->nullable();

            $table->string('consent_version', 40);
            $table->char('consent_text_hash', 64);
            $table->timestamp('data_protection_accepted_at');
            $table->timestamp('attendance_confirmed_at');
            $table->timestamp('official_email_verified_at')->nullable();
            $table->string('confirmation_email_status', 20)->default('pending');
            $table->timestamp('confirmation_email_queued_at')->nullable();
            $table->timestamp('confirmation_email_sent_at')->nullable();
            $table->timestamp('confirmation_email_failed_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'official_email_hash']);
            $table->unique(['event_id', 'verified_email_hash']);
            $table->index(['event_id', 'created_at']);
            $table->index(['confirmation_email_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
