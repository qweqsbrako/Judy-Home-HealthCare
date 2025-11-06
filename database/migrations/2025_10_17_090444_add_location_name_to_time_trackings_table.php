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
        Schema::table('time_trackings', function (Blueprint $table) {
            // Add latitude and longitude columns if they don't exist
            if (!Schema::hasColumn('time_trackings', 'clock_in_latitude')) {
                $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_location');
            }
            
            if (!Schema::hasColumn('time_trackings', 'clock_in_longitude')) {
                $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            }
            
            if (!Schema::hasColumn('time_trackings', 'clock_out_latitude')) {
                $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_location');
            }
            
            if (!Schema::hasColumn('time_trackings', 'clock_out_longitude')) {
                $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_trackings', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_out_latitude',
                'clock_out_longitude'
            ]);
        });
    }
};