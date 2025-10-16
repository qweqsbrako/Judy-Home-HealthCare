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
        Schema::table('care_plans', function (Blueprint $table) {
            // Nurse assignment fields
            $table->foreignId('primary_nurse_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('secondary_nurse_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Assignment details
            $table->text('assignment_notes')->nullable();
            $table->enum('assignment_type', [
                'single_nurse', 
                'team_care'
            ])->nullable();
            
            $table->integer('estimated_hours_per_day')->nullable();
            
            // Assignment tracking
            $table->timestamp('nurse_assigned_at')->nullable();
            $table->timestamp('nurse_accepted_at')->nullable();
            $table->text('nurse_response_notes')->nullable();
            
            // Add indexes for performance
            $table->index('primary_nurse_id');
            $table->index('secondary_nurse_id');
            $table->index('assignment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_plans', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['primary_nurse_id']);
            $table->dropIndex(['secondary_nurse_id']);
            $table->dropIndex(['assignment_type']);
            
            // Drop foreign keys
            $table->dropForeign(['primary_nurse_id']);
            $table->dropForeign(['secondary_nurse_id']);
            
            // Drop columns
            $table->dropColumn([
                'primary_nurse_id',
                'secondary_nurse_id',
                'assignment_notes',
                'assignment_type',
                'estimated_hours_per_day',
                'nurse_assigned_at',
                'nurse_accepted_at',
                'nurse_response_notes'
            ]);
        });
    }
};