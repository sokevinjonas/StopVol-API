<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domains\User\Entities\User;
use App\Domains\User\Repositories\UserRepository;

class UserRepositoryEloquent implements UserRepository
{
    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function findAdminsByEntity(string $entityId): array
    {
        return User::where('entity_id', $entityId)
                   ->where('role', 'entity_admin')
                   ->get()
                   ->toArray();
    }

    public function findUsersWithIncompleteProfile(): array
    {
        return User::where('profile_status', 'incomplete')
                   ->get()
                   ->toArray();
    }

    public function findUsersWithPendingValidation(): array
    {
        return User::where('profile_status', 'pending_validation')
                   ->get()
                   ->toArray();
    }
}
