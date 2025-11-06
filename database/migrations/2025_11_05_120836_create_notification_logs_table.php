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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            
            // User and targeting
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('user_type')->default('patient'); // patient, nurse, admin
            
            // Notification details
            $table->string('notification_type'); // appointment_reminder, medication_reminder, etc.
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Additional metadata
            
            // Related entities (polymorphic approach)
            $table->nullableMorphs('notifiable'); // e.g., care_request_id, schedule_id
            
            // Channels
            $table->boolean('sent_via_push')->default(false);
            $table->boolean('sent_via_email')->default(false);
            $table->boolean('sent_via_sms')->default(false);
            
            // Delivery status
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'read'])->default('pending');
            $table->text('failure_reason')->nullable();
            
            // FCM specific
            $table->string('fcm_message_id')->nullable();
            $table->json('fcm_response')->nullable();
            
            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Priority and scheduling
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('scheduled_for')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'notification_type']);
            $table->index(['status', 'created_at']);
            $table->index('scheduled_for');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};