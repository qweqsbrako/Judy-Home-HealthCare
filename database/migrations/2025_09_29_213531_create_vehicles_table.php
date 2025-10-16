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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            
            // Vehicle Information
            $table->string('vehicle_id')->unique(); // Custom vehicle ID
            $table->enum('vehicle_type', ['ambulance', 'regular']);
            $table->string('registration_number', 20)->unique();
            $table->string('vehicle_color');
            
            // Additional Details (optional)
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->integer('year')->nullable();
            $table->string('vin_number')->nullable(); // Vehicle Identification Number
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->enum('status', ['available', 'in_use', 'maintenance', 'out_of_service'])->default('available');
            
            // Maintenance Information
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->decimal('mileage', 10, 2)->nullable();
            
            // Insurance and Registration
            $table->string('insurance_policy')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('registration_expiry')->nullable();
            
            // Additional Information
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['vehicle_type', 'is_active']);
            $table->index(['status', 'is_available']);
            $table->index('insurance_expiry');
            $table->index('registration_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};