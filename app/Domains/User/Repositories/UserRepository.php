<?php

namespace App\Domains\User\Repositories;

use App\Domains\User\Entities\User;

interface UserRepository
{
    public function findById(string $id): ?User;
    
    public function findByPhone(string $phone): ?User;
    
    public function create(array $data): User;
    
    public function update(User $user, array $data): User;
    
    public function delete(User $user): bool;
    
    public function findAdminsByEntity(string $entityId): array;
    
    public function findUsersWithIncompleteProfile(): array;
    
    public function findUsersWithPendingValidation(): array;
}
