<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domains\Notification\Entities\Notification;
use App\Domains\Notification\Repositories\NotificationRepository;

class NotificationRepositoryEloquent implements NotificationRepository
{
    public function findById(string $id): ?Notification
    {
        return Notification::find($id);
    }

    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function update(Notification $notification, array $data): Notification
    {
        $notification->update($data);
        return $notification->fresh();
    }

    public function delete(Notification $notification): bool
    {
        return $notification->delete();
    }

    public function findByDeclarationId(string $declarationId): array
    {
        return Notification::where('declaration_id', $declarationId)
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->toArray();
    }

    public function findByAdminId(string $adminId): array
    {
        return Notification::where('admin_id', $adminId)
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->toArray();
    }

    public function findByChannel(string $channel): array
    {
        return Notification::where('channel', $channel)
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->toArray();
    }

    public function findPendingNotifications(): array
    {
        return Notification::whereNull('sent_at')
                          ->orderBy('created_at', 'asc')
                          ->get()
                          ->toArray();
    }

    public function findSentNotifications(): array
    {
        return Notification::whereNotNull('sent_at')
                          ->orderBy('sent_at', 'desc')
                          ->get()
                          ->toArray();
    }

    public function findFailedNotifications(): array
    {
        // Notifications that are older than 1 hour and still not sent
        return Notification::whereNull('sent_at')
                          ->where('created_at', '<', now()->subHour())
                          ->orderBy('created_at', 'asc')
                          ->get()
                          ->toArray();
    }

    public function findNotificationsByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return Notification::whereBetween('created_at', [$startDate, $endDate])
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->toArray();
    }

    public function findRecentNotifications(int $hours = 24): array
    {
        return Notification::where('created_at', '>=', now()->subHours($hours))
                          ->orderBy('created_at', 'desc')
                          ->get()
                          ->toArray();
    }

    public function countByChannel(string $channel): int
    {
        return Notification::where('channel', $channel)->count();
    }

    public function countPendingNotifications(): int
    {
        return Notification::whereNull('sent_at')->count();
    }

    public function findNotificationsForUser(string $userId): array
    {
        return Notification::join('declarations', 'notifications.declaration_id', '=', 'declarations.id')
                          ->where('declarations.user_id', $userId)
                          ->select('notifications.*')
                          ->orderBy('notifications.created_at', 'desc')
                          ->get()
                          ->toArray();
    }
}
