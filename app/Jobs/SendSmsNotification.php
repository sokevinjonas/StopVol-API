<?php

namespace App\Jobs;

use App\Domains\Notification\Entities\Notification;
use App\Domains\Notification\Services\NotificationService;
use App\Infrastructure\Messaging\SmsSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 minute between retries

    public function __construct(
        private Notification $notification
    ) {}

    public function handle(SmsSender $smsSender, NotificationService $notificationService): void
    {
        try {
            // Get the user's phone number through the declaration
            $declaration = app(\App\Domains\Declaration\Repositories\DeclarationRepository::class)
                ->findById($this->notification->declaration_id);
            
            if (!$declaration) {
                Log::error('Declaration not found for notification', [
                    'notification_id' => $this->notification->id,
                    'declaration_id' => $this->notification->declaration_id
                ]);
                return;
            }

            $user = app(\App\Domains\User\Repositories\UserRepository::class)
                ->findById($declaration['user_id']);
            
            if (!$user) {
                Log::error('User not found for notification', [
                    'notification_id' => $this->notification->id,
                    'user_id' => $declaration['user_id']
                ]);
                return;
            }

            // Format phone number
            $phone = $smsSender->formatPhoneNumber($user->phone);

            // Validate phone number
            if (!$smsSender->validatePhoneNumber($phone)) {
                Log::error('Invalid phone number for SMS notification', [
                    'notification_id' => $this->notification->id,
                    'phone' => $phone
                ]);
                return;
            }

            // Send SMS
            $success = $smsSender->send($phone, $this->notification->message);

            if ($success) {
                // Mark notification as sent
                $notificationService->markNotificationAsSent($this->notification);
                
                Log::info('SMS notification sent successfully', [
                    'notification_id' => $this->notification->id,
                    'phone' => $phone
                ]);
            } else {
                Log::error('Failed to send SMS notification', [
                    'notification_id' => $this->notification->id,
                    'phone' => $phone
                ]);
                
                // This will trigger a retry
                throw new \Exception('SMS sending failed');
            }

        } catch (\Exception $e) {
            Log::error('Error processing SMS notification job', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SMS notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
