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
        Schema::table('incident_reports', function (Blueprint $table) {
            // Audit trail fields for tracking who made changes
            $table->foreignId('last_updated_by')->nullable()->after('assigned_to')->constrained('users');
            
            // Closure tracking
            $table->foreignId('closed_by')->nullable()->after('last_updated_by')->constrained('users');
            $table->timestamp('closed_at')->nullable()->after('closed_by');
            $table->text('closure_reason')->nullable()->after('closed_at');
            
            // Resolution tracking
            $table->foreignId('resolved_by')->nullable()->after('closure_reason')->constrained('users');
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            
            // Add indexes for better performance
            $table->index(['status', 'closed_at']);
            $table->index(['resolved_by', 'resolved_at']);
            $table->index(['closed_by', 'closed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->dropForeign(['last_updated_by']);
            $table->dropForeign(['closed_by']);
            $table->dropForeign(['resolved_by']);
            
            $table->dropIndex(['status', 'closed_at']);
            $table->dropIndex(['resolved_by', 'resolved_at']);
            $table->dropIndex(['closed_by', 'closed_at']);
            
            $table->dropColumn([
                'last_updated_by',
                'closed_by', 
                'closed_at', 
                'closure_reason',
                'resolved_by',
                'resolved_at'
            ]);
        });
    }
};