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
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            
            // Section 1: General Information
            $table->date('report_date'); // Date of Report
            $table->date('incident_date'); // Date of Incident 
            $table->time('incident_time'); // Time of Incident
            $table->string('incident_location')->nullable(); // Location (home/room/washroom/kitchen/other)
            $table->enum('incident_type', ['fall', 'medication_error', 'equipment_failure', 'injury', 'other']); // Type of Incident
            $table->string('incident_type_other')->nullable(); // Other incident type description
            
            // Section 2: Person(s) Involved
            $table->foreignId('patient_id')->nullable()->constrained('users')->onDelete('set null'); // Patient
            $table->integer('patient_age')->nullable(); // Age at time of incident
            $table->enum('patient_sex', ['M', 'F'])->nullable(); // Sex
            $table->string('client_id_case_no')->nullable(); // Client ID/Case No.
            $table->string('staff_family_involved')->nullable(); // Staff/family Involved name
            $table->enum('staff_family_role', ['nurse', 'family', 'other'])->nullable(); // Role
            $table->string('staff_family_role_other')->nullable(); // Other role description
            
            // Section 3: Description of Incident
            $table->text('incident_description'); // Facts only, no opinions
            
            // Section 4: Immediate Actions Taken
            $table->boolean('first_aid_provided')->default(false); // Did you provide First Aid/Medical Care
            $table->text('first_aid_description')->nullable(); // If yes, describe
            $table->string('care_provider_name')->nullable(); // Who Provided Care
            $table->boolean('transferred_to_hospital')->default(false); // Was client transferred to hospital
            $table->text('hospital_transfer_details')->nullable(); // If yes, where and mode of transportation
            
            // Section 5: Witness Information
            $table->text('witness_names')->nullable(); // Name(s) of Witness(es)
            $table->text('witness_contacts')->nullable(); // Contact(s)
            
            // Section 6: Follow-Up Actions
            $table->string('reported_to_supervisor')->nullable(); // Reported To (Supervisor/Manager)
            $table->text('corrective_preventive_actions')->nullable(); // Corrective/Preventive Actions Planned
            
            // Section 7: Signatures & Reporting
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade'); // Staff Reporting
            $table->timestamp('reported_at')->useCurrent(); // Report submission timestamp
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null'); // Supervisor/Manager Review
            $table->timestamp('reviewed_at')->nullable(); // Review timestamp
            
            // Additional tracking fields
            $table->enum('status', ['pending', 'under_review', 'investigated', 'resolved', 'closed'])->default('pending');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            // File attachments
            $table->json('attachments')->nullable()->comment('Array of file paths for photos, documents, etc.');
            
            // Additional notes and actions
            $table->text('investigation_notes')->nullable();
            $table->text('final_resolution')->nullable();
            $table->text('prevention_measures')->nullable();
            
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['patient_id', 'incident_date']);
            $table->index(['reported_by', 'reported_at']);
            $table->index(['status', 'severity']);
            $table->index(['incident_type', 'incident_date']);
            $table->index('incident_date');
            $table->index('follow_up_date');
            $table->index(['reviewed_by', 'reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};