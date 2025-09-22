<?php

namespace App\Domains\Notification\Repositories;

use App\Domains\Notification\Entities\Notification;

interface NotificationRepository
{
    public function findById(string $id): ?Notification;
    
    public function create(array $data): Notification;
    
    public function update(Notification $notification, array $data): Notification;
    
    public function delete(Notification $notification): bool;
    
    public function findByDeclarationId(string $declarationId): array;
    
    public function findByAdminId(string $adminId): array;
    
    public function findByChannel(string $channel): array;
    
    public function findPendingNotifications(): array;
    
    public function findSentNotifications(): array;
    
    public function findFailedNotifications(): array;
    
    public function findNotificationsByDateRange(\DateTime $startDate, \DateTime $endDate): array;
    
    public function findRecentNotifications(int $hours = 24): array;
    
    public function countByChannel(string $channel): int;
    
    public function countPendingNotifications(): int;
    
    public function findNotificationsForUser(string $userId): array;
}
