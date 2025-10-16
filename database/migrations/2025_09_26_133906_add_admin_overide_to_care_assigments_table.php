<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_assignments', function (Blueprint $table) {
            $table->boolean('admin_override')->default(false)->after('workload_balance_score');
            $table->text('admin_override_reason')->nullable()->after('admin_override');
            $table->timestamp('admin_override_at')->nullable()->after('admin_override_reason');
            $table->foreignId('admin_override_by')->nullable()->constrained('users')->after('admin_override_at');
        });
    }

    public function down(): void
    {
        Schema::table('care_assignments', function (Blueprint $table) {
            $table->dropForeign(['admin_override_by']);
            $table->dropColumn(['admin_override', 'admin_override_reason', 'admin_override_at', 'admin_override_by']);
        });
    }
};