<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domains\Declaration\Entities\Declaration;
use App\Domains\Declaration\Repositories\DeclarationRepository;

class DeclarationRepositoryEloquent implements DeclarationRepository
{
    public function findById(string $id): ?Declaration
    {
        return Declaration::find($id);
    }

    public function create(array $data): Declaration
    {
        return Declaration::create($data);
    }

    public function update(Declaration $declaration, array $data): Declaration
    {
        $declaration->update($data);
        return $declaration->fresh();
    }

    public function delete(Declaration $declaration): bool
    {
        return $declaration->delete();
    }

    public function findByUserId(string $userId): array
    {
        return Declaration::where('user_id', $userId)
                         ->orderBy('created_at', 'desc')
                         ->get()
                         ->toArray();
    }

    public function findByPlateNumber(string $plateNumber): array
    {
        return Declaration::where('plate_number', 'LIKE', '%' . $plateNumber . '%')
                         ->orderBy('created_at', 'desc')
                         ->get()
                         ->toArray();
    }

    public function findByChassisNumber(string $chassisNumber): array
    {
        return Declaration::where('chassis_number', 'LIKE', '%' . $chassisNumber . '%')
                         ->orderBy('created_at', 'desc')
                         ->get()
                         ->toArray();
    }

    public function findByCardNumber(string $cardNumber): array
    {
        return Declaration::where('card_number', 'LIKE', '%' . $cardNumber . '%')
                         ->orderBy('created_at', 'desc')
                         ->get()
                         ->toArray();
    }

    public function findByStatus(string $status): array
    {
        return Declaration::where('status', $status)
                         ->orderBy('created_at', 'desc')
                         ->get()
                         ->toArray();
    }

    public function findPendingDeclarations(): array
    {
        return $this->findByStatus('pending');
    }

    public function findFoundDeclarations(): array
    {
        return $this->findByStatus('found');
    }

    public function findClosedDeclarations(): array
    {
        return $this->findByStatus('closed');
    }

    public function searchByIdentifier(string $identifier): array
    {
        return Declaration::where(function ($query) use ($identifier) {
            $query->where('plate_number', 'LIKE', '%' . $identifier . '%')
                  ->orWhere('chassis_number', 'LIKE', '%' . $identifier . '%')
                  ->orWhere('card_number', 'LIKE', '%' . $identifier . '%');
        })
        ->orderBy('created_at', 'desc')
        ->get()
        ->toArray();
    }

    public function findAll(array $filters = [], int $limit = null, int $offset = null): array
    {
        $query = Declaration::query();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['brand'])) {
            $query->where('brand', 'LIKE', '%' . $filters['brand'] . '%');
        }

        if (isset($filters['model'])) {
            $query->where('model', 'LIKE', '%' . $filters['model'] . '%');
        }

        if (isset($filters['color'])) {
            $query->where('color', 'LIKE', '%' . $filters['color'] . '%');
        }

        if (isset($filters['theft_date_from'])) {
            $query->where('theft_date', '>=', $filters['theft_date_from']);
        }

        if (isset($filters['theft_date_to'])) {
            $query->where('theft_date', '<=', $filters['theft_date_to']);
        }

        // Apply pagination
        if ($limit) {
            $query->limit($limit);
        }

        if ($offset) {
            $query->offset($offset);
        }

        return $query->orderBy('created_at', 'desc')
                    ->get()
                    ->toArray();
    }

    public function count(array $filters = []): int
    {
        $query = Declaration::query();

        // Apply same filters as findAll
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['brand'])) {
            $query->where('brand', 'LIKE', '%' . $filters['brand'] . '%');
        }

        if (isset($filters['model'])) {
            $query->where('model', 'LIKE', '%' . $filters['model'] . '%');
        }

        if (isset($filters['color'])) {
            $query->where('color', 'LIKE', '%' . $filters['color'] . '%');
        }

        if (isset($filters['theft_date_from'])) {
            $query->where('theft_date', '>=', $filters['theft_date_from']);
        }

        if (isset($filters['theft_date_to'])) {
            $query->where('theft_date', '<=', $filters['theft_date_to']);
        }

        return $query->count();
    }

    public function findRecentDeclarations(int $days = 30): array
    {
        return Declaration::where('created_at', '>=', now()->subDays($days))
                         ->orderBy('created_at', 'desc')
                         ->get()
                         ->toArray();
    }

    public function findDeclarationsByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return Declaration::whereBetween('created_at', [$startDate, $endDate])
                         ->orderBy('created_at', 'desc')
                         ->get()
                         ->toArray();
    }
}
