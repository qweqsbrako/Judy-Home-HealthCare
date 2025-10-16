<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Illuminate\Console\Command;

class SendScheduleReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:send-reminders 
                            {--type=daily : Type of reminder: daily or urgent}
                            {--max-reminders=3 : Maximum reminders per schedule}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for upcoming schedules to nurses via email and SMS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $maxReminders = (int) $this->option('max-reminders');
        $dryRun = $this->option('dry-run');

        // Validate type
        if (!in_array($type, ['daily', 'urgent'])) {
            $this->error("Invalid type '{$type}'. Use --type=daily or --type=urgent");
            return 1;
        }

        // Get schedules based on type
        if ($type === 'daily') {
            $schedules = $this->getDailySchedules($maxReminders);
            $this->info("Checking for schedules happening tomorrow...");
        } else {
            $schedules = $this->getUrgentSchedules($maxReminders);
            $this->info("Checking for schedules starting in next 2 hours...");
        }

        // Check if any schedules found
        if ($schedules->isEmpty()) {
            $this->info('No schedules found that need reminders.');
            return 0;
        }

        $this->info("Found {$schedules->count()} schedule(s) to remind.");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No reminders will be sent');
            $this->newLine();
        }

        // Send reminders with progress bar
        $summary = $this->sendReminders($schedules, $dryRun);

        // Display summary
        $this->displaySummary($summary, $dryRun);

        return 0;
    }

    /**
     * Get schedules for daily reminder (tomorrow's schedules)
     */
    protected function getDailySchedules(int $maxReminders)
    {
        return Schedule::with(['nurse', 'carePlan.patient'])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereDate('schedule_date', now()->addDay()->toDateString())
            ->where(function($query) use ($maxReminders) {
                $query->where(function($q) {
                    // Never sent reminder OR not sent today
                    $q->whereNull('last_reminder_sent')
                      ->orWhereDate('last_reminder_sent', '<', now()->toDateString());
                })
                ->where('reminder_count', '<', $maxReminders);
            })
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get schedules for urgent reminder (starting in next 2 hours)
     */
    protected function getUrgentSchedules(int $maxReminders)
    {
        $now = now();
        $twoHoursFromNow = now()->addHours(2);

        return Schedule::with(['nurse', 'carePlan.patient'])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereRaw("CONCAT(schedule_date, ' ', start_time) >= ?", [$now->toDateTimeString()])
            ->whereRaw("CONCAT(schedule_date, ' ', start_time) <= ?", [$twoHoursFromNow->toDateTimeString()])
            ->where(function($query) use ($maxReminders) {
                $query->where(function($q) {
                    // Never sent reminder OR not sent today
                    $q->whereNull('last_reminder_sent')
                      ->orWhereDate('last_reminder_sent', '<', now()->toDateString());
                })
                ->where('reminder_count', '<', $maxReminders);
            })
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Send reminders to all schedules
     */
    protected function sendReminders($schedules, bool $dryRun): array
    {
        $bar = $this->output->createProgressBar($schedules->count());
        $bar->start();

        $summary = [
            'total' => $schedules->count(),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'email_sent' => 0,
            'sms_sent' => 0,
            'details' => []
        ];

        foreach ($schedules as $schedule) {
            if ($dryRun) {
                $this->processDryRun($schedule, $summary);
            } else {
                $this->processRealSend($schedule, $summary);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        return $summary;
    }

    /**
     * Process dry run for a schedule
     */
    protected function processDryRun($schedule, array &$summary): void
    {
        $scheduleDateTime = $schedule->schedule_date->format('Y-m-d') . ' ' . $schedule->start_time;
        $nurseName = $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name;
        $patientName = $schedule->carePlan?->patient 
            ? $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name
            : 'No patient';

        $this->newLine();
        $this->line("Schedule #{$schedule->id}:");
        $this->line("  Nurse: {$nurseName}");
        $this->line("  Patient: {$patientName}");
        $this->line("  Start: {$scheduleDateTime}");
        $this->line("  Location: {$schedule->location}");
        $this->line("  Email: {$schedule->nurse->email}");
        $this->line("  Phone: {$schedule->nurse->phone}");
        
        $summary['success']++;
        $summary['email_sent']++;
        $summary['sms_sent']++;
    }

    /**
     * Process real send for a schedule
     */
    protected function processRealSend($schedule, array &$summary): void
    {
        try {
            $result = $schedule->sendReminder();
            
            if ($result['overall_success']) {
                $summary['success']++;
                
                if ($result['email']['sent']) {
                    $summary['email_sent']++;
                }
                
                if ($result['sms']['sent']) {
                    $summary['sms_sent']++;
                }
                
                $summary['details'][] = [
                    'id' => $schedule->id,
                    'nurse' => $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name,
                    'status' => 'success',
                    'email' => $result['email']['sent'] ? 'sent' : 'failed',
                    'sms' => $result['sms']['sent'] ? 'sent' : 'failed'
                ];
            } else {
                // Check if it was skipped or failed
                if (str_contains($result['email']['message'], 'already sent today') ||
                    str_contains($result['email']['message'], 'Maximum reminders')) {
                    $summary['skipped']++;
                } else {
                    $summary['failed']++;
                }
                
                $summary['details'][] = [
                    'id' => $schedule->id,
                    'nurse' => $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name,
                    'status' => 'failed',
                    'reason' => $result['email']['message']
                ];
            }
        } catch (\Exception $e) {
            $summary['failed']++;
            
            $this->error("Failed to send reminder for schedule #{$schedule->id}: {$e->getMessage()}");
            
            $summary['details'][] = [
                'id' => $schedule->id,
                'nurse' => $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name,
                'status' => 'error',
                'reason' => $e->getMessage()
            ];
        }
    }

    /**
     * Display summary of results
     */
    protected function displaySummary(array $summary, bool $dryRun): void
    {
        $this->info('=== Summary ===');
        
        $tableData = [
            ['Total Schedules', $summary['total']],
            ['Successfully Sent', $summary['success']],
        ];

        if (!$dryRun && $summary['skipped'] > 0) {
            $tableData[] = ['Skipped (sent today)', $summary['skipped']];
        }

        if ($summary['failed'] > 0) {
            $tableData[] = ['Failed', $summary['failed']];
        }

        $tableData[] = ['Emails Sent', $summary['email_sent']];
        $tableData[] = ['SMS Sent', $summary['sms_sent']];

        $this->table(['Metric', 'Count'], $tableData);

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. No actual reminders were sent.');
        } else {
            $this->newLine();
            if ($summary['success'] > 0) {
                $this->info('Reminders sent successfully!');
            }
            
            if ($summary['failed'] > 0) {
                $this->warn("Some reminders failed. Check logs for details.");
            }
        }

        // Show detailed failures if any
        if (!$dryRun && !empty($summary['details'])) {
            $failures = array_filter($summary['details'], function($detail) {
                return $detail['status'] !== 'success';
            });

            if (!empty($failures)) {
                $this->newLine();
                $this->warn('=== Failed/Skipped Details ===');
                foreach ($failures as $failure) {
                    $this->line("Schedule #{$failure['id']} ({$failure['nurse']}): {$failure['reason']}");
                }
            }
        }
    }
}