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
        Schema::table('patient_feedback', function (Blueprint $table) {
            $table->text('admin_response')->nullable()->after('feedback_text');
            $table->timestamp('response_date')->nullable()->after('admin_response');
            $table->foreignId('responded_by')->nullable()->after('response_date')->constrained('users');
            $table->enum('status', ['pending', 'responded'])->default('pending')->after('responded_by');
            
            // Add index for better performance
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_feedback', function (Blueprint $table) {
            $table->dropForeign(['responded_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['admin_response', 'response_date', 'responded_by', 'status']);
        });
    }
};