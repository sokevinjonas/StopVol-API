<?php

namespace App\Domains\OTP\Repositories;

use App\Domains\OTP\Entities\OtpCode;

interface OtpRepository
{
    public function findById(string $id): ?OtpCode;
    
    public function create(array $data): OtpCode;
    
    public function update(OtpCode $otpCode, array $data): OtpCode;
    
    public function delete(OtpCode $otpCode): bool;
    
    public function findByPhone(string $phone): array;
    
    public function findValidByPhone(string $phone): ?OtpCode;
    
    public function findLatestByPhone(string $phone): ?OtpCode;
    
    public function findExpiredCodes(): array;
    
    public function findUsedCodes(): array;
    
    public function countRecentByPhone(string $phone, int $minutes = 60): int;
    
    public function deleteExpiredCodes(): int;
    
    public function deleteUsedCodes(): int;
    
    public function findByPhoneAndCode(string $phone, string $code): ?OtpCode;
}
