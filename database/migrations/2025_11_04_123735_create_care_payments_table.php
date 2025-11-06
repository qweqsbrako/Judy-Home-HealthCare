<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_request_id')->constrained('care_requests')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            
            // Payment type
            $table->enum('payment_type', ['assessment_fee', 'care_fee']);
            
            // Amount details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            
            // Payment details
            $table->enum('payment_method', [
                'mobile_money',
                'card',
                'bank_transfer',
                'cash',
                'insurance'
            ])->nullable();
            $table->string('payment_provider')->nullable(); // MTN, Vodafone, AirtelTigo, etc.
            $table->string('transaction_id')->unique()->nullable();
            $table->string('reference_number')->unique();
            $table->string('provider_reference')->nullable();
            
            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
                'cancelled'
            ])->default('pending');
            
            // Additional info
            $table->text('description')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable(); // Store payment gateway response
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['patient_id', 'status']);
            $table->index('reference_number');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_payments');
    }
};