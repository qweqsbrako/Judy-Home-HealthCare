<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_feedback', function (Blueprint $table) {
            // Add schedule_id if it doesn't exist
            if (!Schema::hasColumn('patient_feedback', 'schedule_id')) {
                $table->foreignId('schedule_id')->nullable()
                    ->after('nurse_id')
                    ->constrained('schedules')
                    ->onDelete('cascade');
            }
            
            // Add care_date if it doesn't exist
            if (!Schema::hasColumn('patient_feedback', 'care_date')) {
                $table->date('care_date')->nullable()->after('would_recommend');
            }
            
            // Add status if it doesn't exist
            if (!Schema::hasColumn('patient_feedback', 'status')) {
                $table->enum('status', ['pending', 'responded'])
                    ->default('pending')
                    ->after('care_date');
            }
            
            // Add response_text if it doesn't exist
            if (!Schema::hasColumn('patient_feedback', 'response_text')) {
                $table->text('response_text')->nullable()->after('status');
            }
            
            // Add responded_by if it doesn't exist
            if (!Schema::hasColumn('patient_feedback', 'responded_by')) {
                $table->foreignId('responded_by')->nullable()
                    ->after('response_text')
                    ->constrained('users')
                    ->onDelete('set null');
            }
            
            // Add responded_at if it doesn't exist
            if (!Schema::hasColumn('patient_feedback', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('responded_by');
            }
            
            // Add index for schedule_id
            $table->index('schedule_id');
        });
    }

    public function down(): void
    {
        Schema::table('patient_feedback', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropColumn(['schedule_id', 'care_date', 'status', 'response_text', 'responded_by', 'responded_at']);
        });
    }
};