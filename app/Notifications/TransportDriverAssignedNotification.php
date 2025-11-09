<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\TransportRequest;
use App\Services\SmsService;
use App\Services\FcmNotificationService;
use App\Models\NotificationLog;

class TransportDriverAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = 60;

    protected TransportRequest $transportRequest;

    public function __construct(TransportRequest $transportRequest)
    {
        $this->transportRequest = $transportRequest;
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        // Send SMS if driver has phone number
        if (!empty($notifiable->phone)) {
            $this->sendSms($notifiable);
        }

        // Send FCM notification to mobile device
        $this->sendFcmNotification($notifiable);

        return $channels;
    }

    protected function sendSms(object $notifiable): void
    {
        try {
            $smsService = app(SmsService::class);
            $message = $this->buildSmsMessage($notifiable);

            $smsResult = $smsService->send($notifiable->phone, $message);

            if ($smsResult['success'] ?? false) {
                \Log::info('Transport assignment SMS sent to driver', [
                    'transport_request_id' => $this->transportRequest->id,
                    'driver_id' => $notifiable->id,
                    'driver_phone' => $notifiable->phone
                ]);
            } else {
                \Log::warning('Failed to send transport assignment SMS', [
                    'transport_request_id' => $this->transportRequest->id,
                    'driver_id' => $notifiable->id,
                    'error' => $smsResult['message'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Exception while sending transport assignment SMS', [
                'transport_request_id' => $this->transportRequest->id,
                'driver_id' => $notifiable->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function buildSmsMessage(object $notifiable): string
    {
        $patientName = $this->transportRequest->patient_name ?? 'Patient';
        $scheduledTime = $this->transportRequest->scheduled_time 
            ? $this->transportRequest->scheduled_time->format('M d, Y \a\t g:i A')
            : 'TBD';
        
        $priorityEmoji = match($this->transportRequest->priority) {
            'emergency' => '🚨',
            'urgent' => '⚡',
            'routine' => '📋',
            default => '📋'
        };

        $message = "{$priorityEmoji} NEW TRANSPORT ASSIGNED\n\n";
        $message .= "Hello {$notifiable->first_name},\n\n";
        $message .= "You have been assigned a new transport request.\n\n";
        
        $message .= "REQUEST DETAILS:\n";
        $message .= "ID: #{$this->transportRequest->id}\n";
        $message .= "Patient: {$patientName}\n";
        $message .= "Type: {$this->transportRequest->type_label}\n";
        $message .= "Priority: {$this->transportRequest->priority_label}\n";
        $message .= "Scheduled: {$scheduledTime}\n\n";
        
        $message .= "PICKUP:\n";
        $message .= "{$this->transportRequest->pickup_location}\n";
        $message .= "{$this->transportRequest->pickup_address}\n\n";
        
        $message .= "DESTINATION:\n";
        $message .= "{$this->transportRequest->destination_location}\n";
        $message .= "{$this->transportRequest->destination_address}\n\n";
        
        if ($this->transportRequest->special_requirements) {
            $message .= "SPECIAL REQUIREMENTS:\n";
            $message .= "{$this->transportRequest->special_requirements}\n\n";
        }
        
        if ($this->transportRequest->contact_person) {
            $message .= "Contact: {$this->transportRequest->contact_person}\n";
        }
        
        if ($this->transportRequest->estimated_cost) {
            $amount = number_format($this->transportRequest->estimated_cost, 2);
            $message .= "Est. Cost: GHS {$amount}\n";
        }
        
        $message .= "\nPlease review the request in the driver app and confirm availability.\n";
        $message .= "\nJudy Home Care Transport";

        return $message;
    }

    protected function sendFcmNotification(object $notifiable): void
    {
        try {
            $fcmService = app(FcmNotificationService::class);
            [$title, $body] = $this->getFcmTitleAndBody();

            $notificationData = [
                'type' => $this->getFcmNotificationType(),
                'title' => $title,
                'body' => $body,
                'priority' => $this->getFcmPriority(),
                'data' => [
                    'transport_request_id' => $this->transportRequest->id,
                    'patient_id' => $this->transportRequest->patient_id,
                    'patient_name' => $this->transportRequest->patient_name,
                    'priority' => $this->transportRequest->priority,
                    'transport_type' => $this->transportRequest->transport_type,
                    'scheduled_time' => $this->transportRequest->scheduled_time?->toISOString(),
                    'pickup_location' => $this->transportRequest->pickup_location,
                    'pickup_address' => $this->transportRequest->pickup_address,
                    'destination_location' => $this->transportRequest->destination_location,
                    'destination_address' => $this->transportRequest->destination_address,
                    'estimated_cost' => $this->transportRequest->estimated_cost,
                    'distance_km' => $this->transportRequest->distance_km,
                    'click_action' => 'OPEN_TRANSPORT_REQUEST',
                    'screen' => 'TransportRequestDetails',
                ],
                'notifiable_type' => TransportRequest::class,
                'notifiable_id' => $this->transportRequest->id,
            ];

            $fcmService->sendToUser($notifiable, $notificationData);

            \Log::info('Transport assignment FCM sent to driver', [
                'transport_request_id' => $this->transportRequest->id,
                'driver_id' => $notifiable->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception while sending transport assignment FCM', [
                'transport_request_id' => $this->transportRequest->id,
                'driver_id' => $notifiable->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function getFcmTitleAndBody(): array
    {
        $priorityEmoji = match($this->transportRequest->priority) {
            'emergency' => '🚨',
            'urgent' => '⚡',
            'routine' => '📋',
            default => '📋'
        };

        $patientName = $this->transportRequest->patient_name ?? 'Patient';
        $typeLabel = $this->transportRequest->type_label;

        $title = "{$priorityEmoji} New Transport Assigned";
        $body = "{$typeLabel} for {$patientName}. Tap to view details.";

        return [$title, $body];
    }

    protected function getFcmNotificationType(): string
    {
        return NotificationLog::TYPE_TRANSPORT_ASSIGNED ?? 'transport_assigned';
    }

    protected function getFcmPriority(): string
    {
        return match($this->transportRequest->priority) {
            'emergency' => 'urgent',
            'urgent' => 'high',
            'routine' => 'normal',
            default => 'normal'
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $patientName = $this->transportRequest->patient_name ?? 'Patient';
        $typeLabel = $this->transportRequest->type_label;
        $scheduledTime = $this->transportRequest->scheduled_time 
            ? $this->transportRequest->scheduled_time->format('l, F d, Y \a\t g:i A')
            : 'To be confirmed';

        $priorityBadge = match($this->transportRequest->priority) {
            'emergency' => '🚨 EMERGENCY',
            'urgent' => '⚡ URGENT',
            'routine' => '📋 ROUTINE',
            default => '📋 ROUTINE'
        };

        $transportUrl = config('app.url') . "/driver/transports/{$this->transportRequest->id}";

        $mailMessage = (new MailMessage)
            ->subject("🚗 New Transport Assigned - {$typeLabel} - Judy Home Care")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("You have been assigned a new transport request.")
            ->line('')
            ->line("**Priority:** {$priorityBadge}")
            ->line("**Request ID:** #{$this->transportRequest->id}")
            ->line("**Patient:** {$patientName}")
            ->line("**Type:** {$typeLabel}")
            ->line("**Scheduled Time:** {$scheduledTime}")
            ->line('')
            ->line("**Pickup Location:**")
            ->line($this->transportRequest->pickup_location)
            ->line($this->transportRequest->pickup_address)
            ->line('')
            ->line("**Destination:**")
            ->line($this->transportRequest->destination_location)
            ->line($this->transportRequest->destination_address);

        if ($this->transportRequest->reason) {
            $mailMessage->line('')
                       ->line("**Reason for Transport:**")
                       ->line($this->transportRequest->reason);
        }

        if ($this->transportRequest->special_requirements) {
            $mailMessage->line('')
                       ->line("**Special Requirements:**")
                       ->line($this->transportRequest->special_requirements);
        }

        if ($this->transportRequest->contact_person) {
            $mailMessage->line('')
                       ->line("**Contact Person:** {$this->transportRequest->contact_person}");
        }

        if ($this->transportRequest->estimated_cost) {
            $amount = number_format($this->transportRequest->estimated_cost, 2);
            $mailMessage->line("**Estimated Cost:** GHS {$amount}");
        }

        if ($this->transportRequest->distance_km) {
            $distance = number_format($this->transportRequest->distance_km, 2);
            $mailMessage->line("**Distance:** {$distance} km");
        }

        $mailMessage->line('')
                   ->action('View Transport Details', $transportUrl)
                   ->line('Please review the request in your driver app and confirm your availability.')
                   ->line('')
                   ->line('If you cannot accept this assignment, please contact dispatch immediately.');

        return $mailMessage->salutation('Safe travels, The Judy Home Care Transport Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'transport_request_id' => $this->transportRequest->id,
            'patient_id' => $this->transportRequest->patient_id,
            'patient_name' => $this->transportRequest->patient_name,
            'transport_type' => $this->transportRequest->transport_type,
            'priority' => $this->transportRequest->priority,
            'scheduled_time' => $this->transportRequest->scheduled_time?->toISOString(),
            'pickup_location' => $this->transportRequest->pickup_location,
            'destination_location' => $this->transportRequest->destination_location,
            'type' => 'transport_driver_assigned',
        ];
    }

    public function failed(\Exception $exception)
    {
        \Log::error('Transport driver assignment notification failed', [
            'transport_request_id' => $this->transportRequest->id,
            'driver_id' => $this->transportRequest->driver_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}