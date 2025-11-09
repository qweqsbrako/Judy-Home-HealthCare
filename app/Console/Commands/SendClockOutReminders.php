<?php

namespace App\Console\Commands;

use App\Models\TimeTracking;
use App\Models\NotificationLog;
use App\Services\FcmNotificationService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendClockOutReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time-tracking:send-clockout-reminders
                            {--minutes-overdue=15 : Minutes past end time before sending reminder}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send ONE-TIME reminders to nurses who have not clocked out after their scheduled end time';

    protected FcmNotificationService $fcmService;
    protected SmsService $smsService;

    public function __construct(FcmNotificationService $fcmService, SmsService $smsService)
    {
        parent::__construct();
        $this->fcmService = $fcmService;
        $this->smsService = $smsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutesOverdue = (int) $this->option('minutes-overdue');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for nurses who haven't clocked out {$minutesOverdue} minutes past their end time...");
        $this->info("Note: Each nurse will receive only ONE reminder per session.");
        $this->newLine();

        // Get overdue sessions
        $overdueSessions = $this->getOverdueSessions($minutesOverdue);

        if ($overdueSessions->isEmpty()) {
            $this->info('No overdue clock-outs found.');
            return 0;
        }

        $this->info("Found {$overdueSessions->count()} overdue session(s).");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No notifications will be sent');
            $this->newLine();
        }

        // Send reminders with progress bar
        $summary = $this->sendReminders($overdueSessions, $dryRun);

        // Display summary
        $this->displaySummary($summary, $dryRun);

        return 0;
    }

    /**
     * Get time tracking sessions that are overdue for clock out
     */
    protected function getOverdueSessions(int $minutesOverdue)
    {
        $now = now();
        
        return TimeTracking::with(['nurse', 'schedule', 'patient'])
            ->whereIn('status', ['active', 'paused'])
            ->whereNotNull('schedule_id')
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->whereHas('schedule', function($query) use ($now, $minutesOverdue) {
                $query->where(function($q) use ($now, $minutesOverdue) {
                    // Get the scheduled end datetime
                    $q->whereRaw("
                        TIMESTAMP(schedule_date, end_time) < ?
                    ", [$now->subMinutes($minutesOverdue)->format('Y-m-d H:i:s')]);
                });
            })
            ->get()
            ->filter(function($session) use ($now, $minutesOverdue) {
                // Double check with Carbon to ensure we have the right sessions
                $scheduleEndTime = Carbon::parse(
                    $session->schedule->schedule_date->format('Y-m-d') . ' ' . $session->schedule->end_time
                );
                
                $isOverdue = $scheduleEndTime->addMinutes($minutesOverdue)->isPast();
                
                // IMPORTANT: Check if we've EVER sent a reminder for this specific session
                // This ensures nurses receive only ONE reminder per time tracking session
                // Once they clock out or the session ends, a new session will be eligible for reminders
                $reminderAlreadySent = NotificationLog::where('user_id', $session->nurse_id)
                    ->where('notification_type', NotificationLog::TYPE_CLOCKOUT_REMINDER)
                    ->where('notifiable_type', TimeTracking::class)
                    ->where('notifiable_id', $session->id)
                    ->exists();
                
                return $isOverdue && !$reminderAlreadySent;
            });
    }

    /**
     * Send reminders to all overdue sessions
     */
    protected function sendReminders($sessions, bool $dryRun): array
    {
        $bar = $this->output->createProgressBar($sessions->count());
        $bar->start();

        $summary = [
            'total' => $sessions->count(),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];

        foreach ($sessions as $session) {
            if ($dryRun) {
                $this->processDryRun($session, $summary);
            } else {
                $this->processRealSend($session, $summary);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        return $summary;
    }

    /**
     * Process dry run
     */
    protected function processDryRun($session, array &$summary): void
    {
        $nurse = $session->nurse;
        $schedule = $session->schedule;
        $scheduleEndTime = Carbon::parse($schedule->schedule_date->format('Y-m-d') . ' ' . $schedule->end_time);
        $minutesOverdue = now()->diffInMinutes($scheduleEndTime);
        
        $this->newLine();
        $this->line("Time Tracking #{$session->id}:");
        $this->line("  Nurse: {$nurse->first_name} {$nurse->last_name} ({$nurse->email})");
        $this->line("  Phone: " . ($nurse->phone ?: 'N/A'));
        $this->line("  Scheduled end: {$scheduleEndTime->format('Y-m-d H:i')}");
        $this->line("  Minutes overdue: {$minutesOverdue}");
        $this->line("  Clock in time: {$session->start_time->format('Y-m-d H:i')}");
        $this->line("  Current status: {$session->status}");
        
        $summary['success']++;
    }

    /**
     * Process real send
     */
    protected function processRealSend($session, array &$summary): void
    {
        try {
            $nurse = $session->nurse;
            $schedule = $session->schedule;
            $scheduleEndTime = Carbon::parse($schedule->schedule_date->format('Y-m-d') . ' ' . $schedule->end_time);
            $minutesOverdue = now()->diffInMinutes($scheduleEndTime);
            
            $results = [
                'fcm' => ['sent' => false, 'message' => ''],
                'email' => ['sent' => false, 'message' => ''],
                'sms' => ['sent' => false, 'message' => '']
            ];

            // 1. Send FCM notification
            try {
                $fcmData = $this->prepareFcmNotificationData($session, $scheduleEndTime, $minutesOverdue);
                $fcmResult = $this->fcmService->sendToUser($nurse, $fcmData);
                $results['fcm'] = $fcmResult;
            } catch (\Exception $e) {
                $results['fcm']['message'] = $e->getMessage();
                \Log::error("FCM notification failed for time tracking #{$session->id}: " . $e->getMessage());
            }

            // 2. Send Email
            try {
                $this->sendEmail($nurse, $session, $scheduleEndTime, $minutesOverdue);
                $results['email'] = ['sent' => true, 'message' => 'Email sent successfully'];
            } catch (\Exception $e) {
                $results['email']['message'] = $e->getMessage();
                \Log::error("Email failed for time tracking #{$session->id}: " . $e->getMessage());
            }

            // 3. Send SMS
            try {
                $smsMessage = $this->prepareSmsMessage($session, $scheduleEndTime, $minutesOverdue);
                $smsResult = $this->smsService->send($nurse->phone, $smsMessage);
                $results['sms'] = $smsResult;
            } catch (\Exception $e) {
                $results['sms']['message'] = $e->getMessage();
                \Log::error("SMS failed for time tracking #{$session->id}: " . $e->getMessage());
            }

            // Determine overall success
            $anySuccess = $results['fcm']['sent'] || $results['email']['sent'] || $results['sms']['sent'];

            if ($anySuccess) {
                $summary['success']++;
                $channels = [];
                if ($results['fcm']['sent']) $channels[] = 'FCM';
                if ($results['email']['sent']) $channels[] = 'Email';
                if ($results['sms']['sent']) $channels[] = 'SMS';
                
                $summary['details'][] = [
                    'id' => $session->id,
                    'nurse' => $nurse->first_name . ' ' . $nurse->last_name,
                    'status' => 'success',
                    'channels' => implode(', ', $channels),
                ];
            } else {
                $summary['failed']++;
                $summary['details'][] = [
                    'id' => $session->id,
                    'nurse' => $nurse->first_name . ' ' . $nurse->last_name,
                    'status' => 'failed',
                    'reason' => 'All channels failed',
                ];
            }

        } catch (\Exception $e) {
            $summary['failed']++;
            
            $this->error("Failed to send reminder for time tracking #{$session->id}: {$e->getMessage()}");
            
            $summary['details'][] = [
                'id' => $session->id,
                'nurse' => $session->nurse->first_name . ' ' . $session->nurse->last_name,
                'status' => 'error',
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare FCM notification data
     */
    protected function prepareFcmNotificationData($session, $scheduleEndTime, $minutesOverdue): array
    {
        $patientName = 'your patient';
        if ($session->patient) {
            $patientName = $session->patient->first_name . ' ' . $session->patient->last_name;
        }

        return [
            'type' => NotificationLog::TYPE_CLOCKOUT_REMINDER,
            'title' => 'Clock Out Reminder',
            'body' => "You were scheduled to end your shift with {$patientName} at {$scheduleEndTime->format('g:i A')}. Please clock out now if you have finished.",
            'priority' => 'high',
            'data' => [
                'time_tracking_id' => $session->id,
                'schedule_id' => $session->schedule_id,
                'scheduled_end_time' => $scheduleEndTime->toIso8601String(),
                'minutes_overdue' => $minutesOverdue,
                'patient_name' => $patientName,
                'click_action' => 'OPEN_TIME_TRACKING',
            ],
            'notifiable_type' => TimeTracking::class,
            'notifiable_id' => $session->id,
        ];
    }

    /**
     * Send email reminder
     */
    protected function sendEmail($nurse, $session, $scheduleEndTime, $minutesOverdue): void
    {
        $patientName = 'your patient';
        if ($session->patient) {
            $patientName = $session->patient->first_name . ' ' . $session->patient->last_name;
        }

        $data = [
            'nurse_name' => $nurse->first_name,
            'patient_name' => $patientName,
            'scheduled_end_time' => $scheduleEndTime->format('g:i A'),
            'clock_in_time' => $session->start_time->format('g:i A'),
            'minutes_overdue' => $minutesOverdue,
            'shift_date' => $scheduleEndTime->format('l, F j, Y'),
        ];

        Mail::send('emails.clockout-reminder', $data, function($message) use ($nurse) {
            $message->to($nurse->email)
                    ->subject('Reminder: Please Clock Out');
        });
    }

    /**
     * Prepare SMS message
     */
    protected function prepareSmsMessage($session, $scheduleEndTime, $minutesOverdue): string
    {
        $nurseName = $session->nurse->first_name;
        
        return "Hi {$nurseName}, you were scheduled to end your shift at {$scheduleEndTime->format('g:i A')}. Please clock out if you have finished. Thank you!";
    }

    /**
     * Display summary
     */
    protected function displaySummary(array $summary, bool $dryRun): void
    {
        $this->info('=== Summary ===');
        
        $tableData = [
            ['Total Overdue Sessions', $summary['total']],
            ['Successfully Sent', $summary['success']],
        ];

        if ($summary['skipped'] > 0) {
            $tableData[] = ['Skipped', $summary['skipped']];
        }

        if ($summary['failed'] > 0) {
            $tableData[] = ['Failed', $summary['failed']];
        }

        $this->table(['Metric', 'Count'], $tableData);

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. No actual notifications were sent.');
        } else {
            $this->newLine();
            if ($summary['success'] > 0) {
                $this->info('Clock-out reminders sent successfully!');
            }
            
            if ($summary['failed'] > 0) {
                $this->warn("Some reminders failed. Check logs for details.");
            }
        }

        // Show detailed results
        if (!$dryRun && !empty($summary['details'])) {
            $this->newLine();
            $this->info('=== Details ===');
            foreach ($summary['details'] as $detail) {
                if ($detail['status'] === 'success') {
                    $this->line("✓ Time Tracking #{$detail['id']} ({$detail['nurse']}): {$detail['channels']}");
                } else {
                    $this->error("✗ Time Tracking #{$detail['id']} ({$detail['nurse']}): {$detail['reason']}");
                }
            }
        }
    }
}