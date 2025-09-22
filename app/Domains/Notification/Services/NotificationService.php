<?php

namespace App\Domains\Notification\Services;

use App\Domains\Notification\Entities\Notification;
use App\Domains\Notification\Repositories\NotificationRepository;
use App\Domains\Notification\Events\NotificationSent;
use App\Domains\Declaration\Entities\Declaration;
use App\Domains\User\Entities\User;
use App\Jobs\SendSmsNotification;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {}

    public function sendDeclarationFoundNotification(
        Declaration $declaration, 
        User $admin, 
        string $message = null
    ): array {
        $message = $message ?? $this->getDefaultFoundMessage($declaration);
        
        $notifications = [];
        
        // Send SMS notification
        $smsNotification = $this->createSmsNotification($declaration->id, $message, $admin->id);
        $notifications[] = $smsNotification;
        
        // Send App notification
        $appNotification = $this->createAppNotification($declaration->id, $message, $admin->id);
        $notifications[] = $appNotification;
        
        return $notifications;
    }

    public function sendDeclarationStatusUpdateNotification(
        Declaration $declaration, 
        User $admin, 
        string $newStatus,
        string $message = null
    ): array {
        $message = $message ?? $this->getDefaultStatusUpdateMessage($declaration, $newStatus);
        
        $notifications = [];
        
        // Send SMS notification
        $smsNotification = $this->createSmsNotification($declaration->id, $message, $admin->id);
        $notifications[] = $smsNotification;
        
        // Send App notification
        $appNotification = $this->createAppNotification($declaration->id, $message, $admin->id);
        $notifications[] = $appNotification;
        
        return $notifications;
    }

    public function sendCustomNotification(
        Declaration $declaration, 
        User $admin, 
        string $message,
        array $channels = ['sms', 'app']
    ): array {
        $notifications = [];
        
        if (in_array('sms', $channels)) {
            $notifications[] = $this->createSmsNotification($declaration->id, $message, $admin->id);
        }
        
        if (in_array('app', $channels)) {
            $notifications[] = $this->createAppNotification($declaration->id, $message, $admin->id);
        }
        
        return $notifications;
    }

    public function createSmsNotification(
        string $declarationId, 
        string $message, 
        ?string $adminId = null
    ): Notification {
        $notification = $this->notificationRepository->create([
            'declaration_id' => $declarationId,
            'admin_id' => $adminId,
            'message' => $message,
            'channel' => 'sms'
        ]);
        
        // Queue SMS sending job
        Queue::push(new SendSmsNotification($notification));
        
        return $notification;
    }

    public function createAppNotification(
        string $declarationId, 
        string $message, 
        ?string $adminId = null
    ): Notification {
        $notification = $this->notificationRepository->create([
            'declaration_id' => $declarationId,
            'admin_id' => $adminId,
            'message' => $message,
            'channel' => 'app'
        ]);
        
        // Queue push notification job
        Queue::push(new SendPushNotification($notification));
        
        return $notification;
    }

    public function markNotificationAsSent(Notification $notification): Notification
    {
        $notification->markAsSent();
        
        // Fire event
        Event::dispatch(new NotificationSent($notification));
        
        return $notification;
    }

    public function resendNotification(Notification $notification): bool
    {
        if (!$notification->canBeResent()) {
            throw new \InvalidArgumentException('Notification cannot be resent at this time');
        }
        
        // Reset sent_at to null
        $notification->sent_at = null;
        $notification->save();
        
        // Queue appropriate job based on channel
        if ($notification->isSms()) {
            Queue::push(new SendSmsNotification($notification));
        } elseif ($notification->isApp()) {
            Queue::push(new SendPushNotification($notification));
        }
        
        return true;
    }

    public function getNotificationsByDeclaration(Declaration $declaration): array
    {
        return $this->notificationRepository->findByDeclarationId($declaration->id);
    }

    public function getNotificationsByAdmin(User $admin): array
    {
        return $this->notificationRepository->findByAdminId($admin->id);
    }

    public function getPendingNotifications(): array
    {
        return $this->notificationRepository->findPendingNotifications();
    }

    public function getNotificationStats(): array
    {
        return [
            'total_sms' => $this->notificationRepository->countByChannel('sms'),
            'total_app' => $this->notificationRepository->countByChannel('app'),
            'pending' => $this->notificationRepository->countPendingNotifications(),
            'recent_24h' => count($this->notificationRepository->findRecentNotifications(24))
        ];
    }

    private function getDefaultFoundMessage(Declaration $declaration): string
    {
        $vehicleInfo = '';
        if ($declaration->brand && $declaration->model) {
            $vehicleInfo = " ({$declaration->brand} {$declaration->model})";
        }
        
        $identifier = $declaration->plate_number ?? $declaration->chassis_number ?? 'votre véhicule';
        
        return "Bonne nouvelle ! Votre véhicule {$identifier}{$vehicleInfo} déclaré volé a été retrouvé. Veuillez contacter les autorités pour plus d'informations.";
    }

    private function getDefaultStatusUpdateMessage(Declaration $declaration, string $status): string
    {
        $identifier = $declaration->plate_number ?? $declaration->chassis_number ?? 'votre véhicule';
        
        return match($status) {
            'found' => "Votre véhicule {$identifier} a été retrouvé. Contactez les autorités.",
            'closed' => "Le dossier de vol de votre véhicule {$identifier} a été clôturé.",
            default => "Le statut de votre déclaration de vol pour {$identifier} a été mis à jour."
        };
    }
}
