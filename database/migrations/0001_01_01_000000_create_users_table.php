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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 20);
            
            // Role and Status
            $table->enum('role', ['patient', 'nurse', 'doctor', 'admin', 'superadmin']);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            
            // Personal Information
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('ghana_card_number', 20)->nullable()->unique();
            $table->string('avatar')->nullable();
            
            // Professional Information (for nurses and doctors)
            $table->string('license_number', 50)->nullable()->unique();
            $table->string('specialization')->nullable();
            $table->integer('years_experience')->nullable();
            
            // Patient Emergency Contacts
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            
            // Medical Information (JSON fields for patients)
            $table->json('medical_conditions')->nullable();
            $table->json('allergies')->nullable();
            $table->json('current_medications')->nullable();
            
            // Two-Factor Authentication
            $table->boolean('two_factor_enabled')->default(false);
            $table->enum('two_factor_method', ['email', 'sms', 'voice'])->nullable();
            $table->timestamp('two_factor_enabled_at')->nullable();
            $table->timestamp('two_factor_disabled_at')->nullable();
            $table->timestamp('two_factor_verified_at')->nullable();
            
            // Security
            $table->string('security_question')->nullable();
            $table->string('security_answer_hash')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('force_password_change')->default(false);
            $table->string('password_reset_token')->nullable();
            $table->timestamp('password_reset_expires')->nullable();

            
            // System Information
            $table->ipAddress('registered_ip')->nullable();
            $table->json('preferences')->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->string('locale', 10)->default('en');
            
            // Laravel defaults
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['role', 'is_active']);
            $table->index(['verification_status', 'is_verified']);
            $table->index('email_verified_at');
            $table->index('last_login_at');
            $table->index(['license_number', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};