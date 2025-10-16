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
        Schema::create('progress_notes', function (Blueprint $table) {
            $table->id();
            
            // Visit Information
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('nurse_id')->constrained('users')->onDelete('cascade');
            $table->date('visit_date');
            $table->time('visit_time');
            
            // Vital Signs (JSON)
            $table->json('vitals')->nullable();

            // Interventions Provided (JSON)
            $table->json('interventions')->nullable();
            // Observations/Findings
            $table->enum('general_condition', ['improved', 'stable', 'deteriorating']);
            $table->integer('pain_level')->default(0)->comment('Pain scale 0-10');
            $table->text('wound_status')->nullable();
            $table->text('other_observations')->nullable();
            
            // Family/Client Communication
            $table->text('education_provided')->nullable();
            $table->text('family_concerns')->nullable();
            
            // Plan/Next Steps
            $table->text('next_steps')->nullable();
            
            // Additional tracking fields
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_method')->default('digital'); // digital, electronic, manual
            
            // System fields
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['patient_id', 'visit_date']);
            $table->index(['nurse_id', 'visit_date']);
            $table->index('visit_date');
            $table->index('general_condition');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_notes');
    }
};