<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\User;
use App\Models\CarePlan;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all nurses
        $nurses = User::where('role', 'nurse')
            ->where('is_active', true)
            ->where('verification_status', 'verified')
            ->get();

        if ($nurses->isEmpty()) {
            $this->command->warn('No verified nurses found. Please run UserSeeder first.');
            return;
        }

        // Get all active care plans
        $carePlans = CarePlan::where('status', 'active')->get();

        if ($carePlans->isEmpty()) {
            $this->command->warn('No active care plans found. Please run CarePlanSeeder first.');
            return;
        }

        // Get an admin user for created_by
        $admin = User::whereIn('role', ['admin', 'superadmin'])
            ->where('is_active', true)
            ->first();

        if (!$admin) {
            $this->command->warn('No admin user found. Please run UserSeeder first.');
            return;
        }

        $this->command->info('Creating schedules...');

        // Shift configurations
        $shiftConfigs = [
            'morning_shift' => ['start' => '07:00', 'end' => '15:00'],
            'afternoon_shift' => ['start' => '13:00', 'end' => '21:00'],
            'evening_shift' => ['start' => '15:00', 'end' => '23:00'],
            'night_shift' => ['start' => '23:00', 'end' => '07:00'],
            'custom_shift' => ['start' => '09:00', 'end' => '17:00'],
        ];

        $locations = [
            'Patient Home - Accra',
            'Patient Home - Tema',
            'Patient Home - Kumasi',
            'Patient Home - East Legon',
            'Patient Home - Osu',
            'Patient Home - Cantonments',
            'Patient Home - Airport Residential',
            'Patient Home - Labone',
            null, // Some schedules without location
        ];

        $shiftNotes = [
            'Regular home care visit',
            'Medication administration required',
            'Physical therapy session',
            'Wound care and dressing',
            'Vital signs monitoring',
            'Post-operative care',
            'Elderly care assistance',
            'Patient assessment and documentation',
            'Follow-up care visit',
            null, // Some schedules without notes
        ];

        $scheduleCount = 0;

        // Create schedules for the next 60 days
        for ($day = 1; $day <= 60; $day++) {
            $date = Carbon::tomorrow()->addDays($day - 1);

            // Skip Sundays (optional - remove if you want 7-day schedules)
            if ($date->isSunday()) {
                continue;
            }

            // Create 2-5 schedules per day
            $schedulesPerDay = rand(2, 5);

            for ($i = 0; $i < $schedulesPerDay; $i++) {
                // Get a random nurse
                $nurse = $nurses->random();

                // Get care plans where this nurse is assigned
                $nurseCarePlans = $carePlans->filter(function ($carePlan) use ($nurse) {
                    return $carePlan->primary_nurse_id === $nurse->id || 
                           $carePlan->secondary_nurse_id === $nurse->id;
                });

                // Use nurse's care plan if available, otherwise random
                $carePlan = $nurseCarePlans->isNotEmpty() 
                    ? $nurseCarePlans->random() 
                    : $carePlans->random();

                // Get random shift type
                $shiftType = array_rand($shiftConfigs);
                $times = $shiftConfigs[$shiftType];

                // Check if schedule already exists (unique constraint check)
                $exists = Schedule::where('nurse_id', $nurse->id)
                    ->where('schedule_date', $date)
                    ->where('start_time', $times['start'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Check for time conflicts
                $hasConflict = Schedule::where('nurse_id', $nurse->id)
                    ->where('schedule_date', $date)
                    ->where(function ($query) use ($times) {
                        $query->where(function ($q) use ($times) {
                            $q->where('start_time', '<=', $times['start'])
                              ->where('end_time', '>', $times['start']);
                        })->orWhere(function ($q) use ($times) {
                            $q->where('start_time', '<', $times['end'])
                              ->where('end_time', '>=', $times['end']);
                        })->orWhere(function ($q) use ($times) {
                            $q->where('start_time', '>=', $times['start'])
                              ->where('end_time', '<=', $times['end']);
                        });
                    })
                    ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
                    ->exists();

                // Skip if there's a conflict
                if ($hasConflict) {
                    continue;
                }

                $scheduleData = [
                    'nurse_id' => $nurse->id,
                    'care_plan_id' => $carePlan->id,
                    'created_by' => $admin->id,
                    'schedule_date' => $date,
                    'start_time' => $times['start'],
                    'end_time' => $times['end'],
                    'shift_type' => $shiftType,
                    'status' => 'scheduled',
                    'location' => $locations[array_rand($locations)],
                    'shift_notes' => $shiftNotes[array_rand($shiftNotes)],
                    'reminder_count' => 0,
                ];

                try {
                    Schedule::create($scheduleData);
                    $scheduleCount++;
                } catch (\Exception $e) {
                    $this->command->warn("Failed to create schedule: {$e->getMessage()}");
                    continue;
                }
            }
        }

        $this->command->info("Successfully created {$scheduleCount} future schedules.");

        // Create a few specific scenarios for testing

        // 1. Today's schedules (for immediate testing)
        $todaySchedulesCount = 0;
        $todayStartTimes = ['08:00', '10:00', '14:00']; // Different start times to avoid conflicts
        
        $nurseIndex = 0;
        foreach ($nurses->take(3) as $nurse) {
            $startTime = $todayStartTimes[$nurseIndex];
            
            // Check if schedule already exists
            $exists = Schedule::where('nurse_id', $nurse->id)
                ->where('schedule_date', Carbon::today())
                ->where('start_time', $startTime)
                ->exists();

            if ($exists) {
                $this->command->info("Today's schedule for nurse {$nurse->id} already exists, skipping.");
                $nurseIndex++;
                continue;
            }

            $nurseCarePlans = $carePlans->filter(function ($carePlan) use ($nurse) {
                return $carePlan->primary_nurse_id === $nurse->id || 
                       $carePlan->secondary_nurse_id === $nurse->id;
            });

            $carePlan = $nurseCarePlans->isNotEmpty() 
                ? $nurseCarePlans->random() 
                : $carePlans->random();

            try {
                Schedule::create([
                    'nurse_id' => $nurse->id,
                    'care_plan_id' => $carePlan->id,
                    'created_by' => $admin->id,
                    'schedule_date' => Carbon::today(),
                    'start_time' => $startTime,
                    'end_time' => Carbon::parse($startTime)->addHours(8)->format('H:i'),
                    'shift_type' => 'morning_shift',
                    'status' => 'scheduled',
                    'location' => 'Patient Home - Test Location',
                    'shift_notes' => "Today's scheduled visit - for testing",
                    'reminder_count' => 0,
                ]);
                $todaySchedulesCount++;
            } catch (\Exception $e) {
                $this->command->warn("Failed to create today's schedule: {$e->getMessage()}");
            }
            
            $nurseIndex++;
        }

        $this->command->info("Created {$todaySchedulesCount} schedules for today.");

        // 2. Tomorrow's schedules (for reminder testing)
        $tomorrowSchedulesCount = 0;
        $availableShifts = array_keys($shiftConfigs);
        
        foreach ($nurses->take(5) as $nurse) {
            // Try different shift types until we find one that doesn't exist
            $scheduleCreated = false;
            
            foreach ($availableShifts as $shiftType) {
                $times = $shiftConfigs[$shiftType];
                
                // Check if schedule already exists
                $exists = Schedule::where('nurse_id', $nurse->id)
                    ->where('schedule_date', Carbon::tomorrow())
                    ->where('start_time', $times['start'])
                    ->exists();

                if ($exists) {
                    continue; // Try next shift type
                }

                // Check for time conflicts
                $hasConflict = Schedule::where('nurse_id', $nurse->id)
                    ->where('schedule_date', Carbon::tomorrow())
                    ->where(function ($query) use ($times) {
                        $query->where(function ($q) use ($times) {
                            $q->where('start_time', '<=', $times['start'])
                              ->where('end_time', '>', $times['start']);
                        })->orWhere(function ($q) use ($times) {
                            $q->where('start_time', '<', $times['end'])
                              ->where('end_time', '>=', $times['end']);
                        })->orWhere(function ($q) use ($times) {
                            $q->where('start_time', '>=', $times['start'])
                              ->where('end_time', '<=', $times['end']);
                        });
                    })
                    ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
                    ->exists();

                if ($hasConflict) {
                    continue; // Try next shift type
                }

                $nurseCarePlans = $carePlans->filter(function ($carePlan) use ($nurse) {
                    return $carePlan->primary_nurse_id === $nurse->id || 
                           $carePlan->secondary_nurse_id === $nurse->id;
                });

                $carePlan = $nurseCarePlans->isNotEmpty() 
                    ? $nurseCarePlans->random() 
                    : $carePlans->random();

                try {
                    Schedule::create([
                        'nurse_id' => $nurse->id,
                        'care_plan_id' => $carePlan->id,
                        'created_by' => $admin->id,
                        'schedule_date' => Carbon::tomorrow(),
                        'start_time' => $times['start'],
                        'end_time' => $times['end'],
                        'shift_type' => $shiftType,
                        'status' => 'scheduled',
                        'location' => $locations[array_rand($locations)],
                        'shift_notes' => 'Tomorrow\'s visit - ready for reminders',
                        'reminder_count' => 0,
                    ]);
                    $tomorrowSchedulesCount++;
                    $scheduleCreated = true;
                    break; // Successfully created, move to next nurse
                } catch (\Exception $e) {
                    $this->command->warn("Failed to create tomorrow's schedule: {$e->getMessage()}");
                }
            }

            if (!$scheduleCreated) {
                $this->command->info("Could not create tomorrow's schedule for nurse {$nurse->id} - all shifts taken or conflicting.");
            }
        }

        $this->command->info("Created {$tomorrowSchedulesCount} schedules for tomorrow.");

        // Summary
        $totalSchedules = Schedule::count();
        $this->command->info("=================================");
        $this->command->info("Total schedules in database: {$totalSchedules}");
        $this->command->info("Schedules by status:");
        
        foreach (Schedule::getStatuses() as $key => $label) {
            $count = Schedule::where('status', $key)->count();
            $this->command->info("  - {$label}: {$count}");
        }

        $this->command->info("Schedules by shift type:");
        foreach (Schedule::getShiftTypes() as $key => $label) {
            $count = Schedule::where('shift_type', $key)->count();
            $this->command->info("  - {$label}: {$count}");
        }

        $this->command->info("=================================");
    }
}