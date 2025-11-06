<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_fee_structure', function (Blueprint $table) {
            $table->id();
            
            $table->enum('fee_type', ['assessment_fee', 'hourly_rate', 'daily_rate', 'package']);
            $table->enum('care_type', [
                'general_nursing',
                'elderly_care',
                'post_surgical',
                'chronic_disease',
                'palliative_care',
                'rehabilitation',
                'wound_care',
                'medication_management',
                'all' // Default for assessment fees
            ])->default('all');
            
            $table->string('name');
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('base_amount', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->decimal('tax_percentage', 5, 2)->default(0);
            
            // Conditions
            $table->integer('min_hours')->nullable(); // For hourly rates
            $table->integer('max_hours')->nullable();
            $table->integer('duration_days')->nullable(); // For packages
            
            // Availability
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            
            // Region-specific pricing
            $table->json('region_overrides')->nullable(); // {'Accra': 120, 'Kumasi': 100}
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['fee_type', 'care_type', 'is_active']);
        });

        // Insert default assessment fee
        DB::table('care_fee_structure')->insert([
            'fee_type' => 'assessment_fee',
            'care_type' => 'all',
            'name' => 'Home Care Assessment Fee',
            'description' => 'One-time fee for initial home care assessment by a qualified nurse',
            'base_amount' => 150.00,
            'currency' => 'GHS',
            'tax_percentage' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('care_fee_structure');
    }
};