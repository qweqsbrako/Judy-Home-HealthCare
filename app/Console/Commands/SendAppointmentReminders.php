<?php

namespace App\Console\Commands;

use App\Models\CareRequest;
use App\Models\NotificationLog;
use App\Services\FcmNotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders
                            {--type=daily : Type of reminder: daily, hourly, or urgent}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment reminders to patients via push notifications';

    protected FcmNotificationService $fcmService;

    public function __construct(FcmNotificationService $fcmService)
    {
        parent::__construct();
        $this->fcmService = $fcmService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        // Validate type
        if (!in_array($type, ['daily', 'hourly', 'urgent'])) {
            $this->error("Invalid type '{$type}'. Use --type=daily, --type=hourly, or --type=urgent");
            return 1;
        }

        // Get appointments based on type
        $appointments = $this->getAppointments($type);

        if ($appointments->isEmpty()) {
            $this->info('No appointments found that need reminders.');
            return 0;
        }

        $this->info("Found {$appointments->count()} appointment(s) to remind.");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No notifications will be sent');
            $this->newLine();
        }

        // Send reminders with progress bar
        $summary = $this->sendReminders($appointments, $type, $dryRun);

        // Display summary
        $this->displaySummary($summary, $dryRun);

        return 0;
    }

    /**
     * Get appointments based on reminder type
     */
    protected function getAppointments(string $type)
    {
        $query = CareRequest::with(['patient', 'assignedNurse'])
            ->whereNotNull('assessment_scheduled_at')
            ->whereIn('status', ['assessment_scheduled', 'nurse_assigned']);

        return match($type) {
            'daily' => $this->getDailyReminders($query),
            'hourly' => $this->getHourlyReminders($query),
            'urgent' => $this->getUrgentReminders($query),
        };
    }

    /**
     * Get appointments for tomorrow (24-hour reminder)
     */
    protected function getDailyReminders($query)
    {
        $tomorrow = Carbon::tomorrow();
        
        return $query->whereDate('assessment_scheduled_at', $tomorrow->toDateString())
            ->whereDoesntHave('patient.notificationLogs', function ($q) use ($tomorrow) {
                $q->where('notification_type', NotificationLog::TYPE_APPOINTMENT_REMINDER)
                  ->where('data->reminder_type', 'daily')
                  ->whereDate('created_at', now()->toDateString());
            })
            ->get();
    }

    /**
     * Get appointments in next 2 hours (2-hour reminder)
     */
    protected function getHourlyReminders($query)
    {
        $now = now();
        $twoHoursFromNow = now()->addHours(2);

        return $query->whereBetween('assessment_scheduled_at', [$now, $twoHoursFromNow])
            ->whereDoesntHave('patient.notificationLogs', function ($q) {
                $q->where('notification_type', NotificationLog::TYPE_APPOINTMENT_REMINDER)
                  ->where('data->reminder_type', 'hourly')
                  ->where('created_at', '>=', now()->subHour());
            })
            ->get();
    }

    /**
     * Get appointments in next 30 minutes (urgent reminder)
     */
    protected function getUrgentReminders($query)
    {
        $now = now();
        $thirtyMinutesFromNow = now()->addMinutes(30);

        return $query->whereBetween('assessment_scheduled_at', [$now, $thirtyMinutesFromNow])
            ->whereDoesntHave('patient.notificationLogs', function ($q) {
                $q->where('notification_type', NotificationLog::TYPE_APPOINTMENT_REMINDER)
                  ->where('data->reminder_type', 'urgent')
                  ->where('created_at', '>=', now()->subMinutes(15));
            })
            ->get();
    }

    /**
     * Send reminders to all appointments
     */
    protected function sendReminders($appointments, string $type, bool $dryRun): array
    {
        $bar = $this->output->createProgressBar($appointments->count());
        $bar->start();

        $summary = [
            'total' => $appointments->count(),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];

        foreach ($appointments as $appointment) {
            if ($dryRun) {
                $this->processDryRun($appointment, $type, $summary);
            } else {
                $this->processRealSend($appointment, $type, $summary);
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
    protected function processDryRun($appointment, string $type, array &$summary): void
    {
        $patient = $appointment->patient;
        $appointmentTime = Carbon::parse($appointment->assessment_scheduled_at);
        
        $this->newLine();
        $this->line("Appointment #{$appointment->id}:");
        $this->line("  Patient: {$patient->first_name} {$patient->last_name}");
        $this->line("  Scheduled: {$appointmentTime->format('Y-m-d H:i')}");
        $this->line("  Time until: {$appointmentTime->diffForHumans()}");
        $this->line("  Location: {$appointment->service_address}");
        $this->line("  Reminder type: {$type}");
        
        $summary['success']++;
    }

    /**
     * Process real send
     */
    protected function processRealSend($appointment, string $type, array &$summary): void
    {
        try {
            $patient = $appointment->patient;
            $appointmentTime = Carbon::parse($appointment->assessment_scheduled_at);
            
            // Prepare notification data
            $notificationData = $this->prepareNotificationData($appointment, $type);
            
            // Send via FCM
            $result = $this->fcmService->sendToUser($patient, $notificationData);
            
            if ($result['success']) {
                $summary['success']++;
                $summary['details'][] = [
                    'id' => $appointment->id,
                    'patient' => $patient->first_name . ' ' . $patient->last_name,
                    'status' => 'success',
                    'notification_log_id' => $result['notification_log_id'] ?? null,
                ];
            } else {
                if (isset($result['schedule_later'])) {
                    $summary['skipped']++;
                } else {
                    $summary['failed']++;
                }
                
                $summary['details'][] = [
                    'id' => $appointment->id,
                    'patient' => $patient->first_name . ' ' . $patient->last_name,
                    'status' => 'failed',
                    'reason' => $result['message'],
                ];
            }

        } catch (\Exception $e) {
            $summary['failed']++;
            
            $this->error("Failed to send reminder for appointment #{$appointment->id}: {$e->getMessage()}");
            
            $summary['details'][] = [
                'id' => $appointment->id,
                'patient' => $appointment->patient->first_name . ' ' . $appointment->patient->last_name,
                'status' => 'error',
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare notification data
     */
    protected function prepareNotificationData($appointment, string $type): array
    {
        $appointmentTime = Carbon::parse($appointment->assessment_scheduled_at);
        $nurseName = $appointment->assignedNurse 
            ? $appointment->assignedNurse->first_name . ' ' . $appointment->assignedNurse->last_name
            : 'Your assigned nurse';

        [$title, $body, $priority] = match($type) {
            'daily' => [
                'Appointment Tomorrow',
                "Your assessment with {$nurseName} is scheduled for tomorrow at {$appointmentTime->format('g:i A')}. Location: {$appointment->city}",
                'normal'
            ],
            'hourly' => [
                'Appointment in 2 Hours',
                "Your assessment with {$nurseName} starts at {$appointmentTime->format('g:i A')}. Please be ready. Location: {$appointment->service_address}",
                'high'
            ],
            'urgent' => [
                'Appointment Starting Soon!',
                "Your assessment with {$nurseName} starts in 30 minutes at {$appointmentTime->format('g:i A')}. Address: {$appointment->service_address}",
                'urgent'
            ],
        };

        return [
            'type' => NotificationLog::TYPE_APPOINTMENT_REMINDER,
            'title' => $title,
            'body' => $body,
            'priority' => $priority,
            'data' => [
                'care_request_id' => $appointment->id,
                'appointment_time' => $appointmentTime->toIso8601String(),
                'reminder_type' => $type,
                'nurse_name' => $nurseName,
                'location' => $appointment->service_address,
                'click_action' => 'OPEN_APPOINTMENT_DETAILS',
            ],
            'notifiable_type' => CareRequest::class,
            'notifiable_id' => $appointment->id,
        ];
    }

    /**
     * Display summary
     */
    protected function displaySummary(array $summary, bool $dryRun): void
    {
        $this->info('=== Summary ===');
        
        $tableData = [
            ['Total Appointments', $summary['total']],
            ['Successfully Sent', $summary['success']],
        ];

        if ($summary['skipped'] > 0) {
            $tableData[] = ['Skipped (quiet hours)', $summary['skipped']];
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
                $this->info('Appointment reminders sent successfully!');
            }
            
            if ($summary['failed'] > 0) {
                $this->warn("Some reminders failed. Check logs for details.");
            }
        }

        // Show detailed failures
        if (!$dryRun && !empty($summary['details'])) {
            $failures = array_filter($summary['details'], fn($d) => $d['status'] !== 'success');

            if (!empty($failures)) {
                $this->newLine();
                $this->warn('=== Failed/Skipped Details ===');
                foreach ($failures as $failure) {
                    $this->line("Appointment #{$failure['id']} ({$failure['patient']}): {$failure['reason']}");
                }
            }
        }
    }
}