<?php

namespace App\Jobs;

use App\Domains\Notification\Entities\Notification;
use App\Domains\Notification\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 minute between retries

    public function __construct(
        private Notification $notification
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        try {
            // Get the user's FCM token through the declaration
            $declaration = app(\App\Domains\Declaration\Repositories\DeclarationRepository::class)
                ->findById($this->notification->declaration_id);
            
            if (!$declaration) {
                Log::error('Declaration not found for push notification', [
                    'notification_id' => $this->notification->id,
                    'declaration_id' => $this->notification->declaration_id
                ]);
                return;
            }

            $user = app(\App\Domains\User\Repositories\UserRepository::class)
                ->findById($declaration['user_id']);
            
            if (!$user) {
                Log::error('User not found for push notification', [
                    'notification_id' => $this->notification->id,
                    'user_id' => $declaration['user_id']
                ]);
                return;
            }

            // For now, we'll simulate push notification sending
            // In a real implementation, you would:
            // 1. Get user's FCM token from database
            // 2. Send push notification via Firebase Cloud Messaging
            
            $success = $this->sendFirebasePushNotification($user, $this->notification);

            if ($success) {
                // Mark notification as sent
                $notificationService->markNotificationAsSent($this->notification);
                
                Log::info('Push notification sent successfully', [
                    'notification_id' => $this->notification->id,
                    'user_id' => $user->id
                ]);
            } else {
                Log::error('Failed to send push notification', [
                    'notification_id' => $this->notification->id,
                    'user_id' => $user->id
                ]);
                
                // This will trigger a retry
                throw new \Exception('Push notification sending failed');
            }

        } catch (\Exception $e) {
            Log::error('Error processing push notification job', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    private function sendFirebasePushNotification($user, Notification $notification): bool
    {
        $fcmServerKey = config('services.fcm.server_key');
        
        if (empty($fcmServerKey)) {
            Log::warning('FCM server key not configured, simulating push notification');
            return true; // Simulate success for development
        }

        // In a real implementation, you would get the user's FCM token
        // For now, we'll simulate it
        $fcmToken = $user->fcm_token ?? null;
        
        if (empty($fcmToken)) {
            Log::info('User has no FCM token, skipping push notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id
            ]);
            return true; // Consider it successful since user doesn't have the app
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $fcmToken,
                'notification' => [
                    'title' => 'StopVol - Mise à jour',
                    'body' => $notification->message,
                    'icon' => 'ic_notification',
                    'sound' => 'default'
                ],
                'data' => [
                    'notification_id' => $notification->id,
                    'declaration_id' => $notification->declaration_id,
                    'type' => 'declaration_update'
                ]
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['success']) && $responseData['success'] > 0) {
                    return true;
                } else {
                    Log::error('FCM returned unsuccessful response', [
                        'response' => $responseData,
                        'notification_id' => $notification->id
                    ]);
                    return false;
                }
            } else {
                Log::error('FCM HTTP request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'notification_id' => $notification->id
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception while sending FCM notification', [
                'error' => $e->getMessage(),
                'notification_id' => $notification->id
            ]);
            return false;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
