<?php

namespace App\Domains\Declaration\Repositories;

use App\Domains\Declaration\Entities\Declaration;

interface DeclarationRepository
{
    public function findById(string $id): ?Declaration;
    
    public function create(array $data): Declaration;
    
    public function update(Declaration $declaration, array $data): Declaration;
    
    public function delete(Declaration $declaration): bool;
    
    public function findByUserId(string $userId): array;
    
    public function findByPlateNumber(string $plateNumber): array;
    
    public function findByChassisNumber(string $chassisNumber): array;
    
    public function findByCardNumber(string $cardNumber): array;
    
    public function findByStatus(string $status): array;
    
    public function findPendingDeclarations(): array;
    
    public function findFoundDeclarations(): array;
    
    public function findClosedDeclarations(): array;
    
    public function searchByIdentifier(string $identifier): array;
    
    public function findAll(array $filters = [], int $limit = null, int $offset = null): array;
    
    public function count(array $filters = []): int;
    
    public function findRecentDeclarations(int $days = 30): array;
    
    public function findDeclarationsByDateRange(\DateTime $startDate, \DateTime $endDate): array;
}
