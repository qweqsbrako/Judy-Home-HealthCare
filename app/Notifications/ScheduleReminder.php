<?php

namespace App\Notifications;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $schedule;

    /**
     * Create a new notification instance.
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $schedule = $this->schedule;
        $patient = $schedule->carePlan?->patient;
        
        return (new MailMessage)
            ->subject('Schedule Reminder - ' . $schedule->schedule_date->format('M d, Y'))
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('This is a reminder for your upcoming shift:')
            ->line('**Schedule Details:**')
            ->line('📅 Date: ' . $schedule->schedule_date->format('l, F j, Y'))
            ->line('⏰ Time: ' . $schedule->formatted_time_slot)
            ->line('🕐 Shift Type: ' . $schedule->formatted_shift_type)
            ->when($patient, function($mail) use ($patient) {
                return $mail->line('👤 Patient: ' . $patient->first_name . ' ' . $patient->last_name);
            })
            ->when($schedule->location, function($mail) use ($schedule) {
                return $mail->line('📍 Location: ' . $schedule->location);
            })
            ->when($schedule->carePlan, function($mail) use ($schedule) {
                return $mail->line('📋 Care Plan: ' . $schedule->carePlan->title);
            })
            ->when($schedule->shift_notes, function($mail) use ($schedule) {
                return $mail->line('📝 Notes: ' . $schedule->shift_notes);
            })
            ->action('View Schedule Details', route('schedules.index', $schedule->id))
            ->line('Please confirm your availability for this shift.')
            ->line('If you have any questions or need to make changes, please contact your supervisor immediately.')
            ->salutation('Thank you, Judy Home Care Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'schedule_id' => $this->schedule->id,
            'schedule_date' => $this->schedule->schedule_date->toDateString(),
            'start_time' => $this->schedule->start_time,
            'end_time' => $this->schedule->end_time,
            'shift_type' => $this->schedule->shift_type,
            'patient_name' => $this->schedule->carePlan?->patient 
                ? $this->schedule->carePlan->patient->first_name . ' ' . $this->schedule->carePlan->patient->last_name 
                : null,
            'location' => $this->schedule->location,
            'message' => 'Schedule reminder for ' . $this->schedule->schedule_date->format('M d, Y'),
        ];
    }
}