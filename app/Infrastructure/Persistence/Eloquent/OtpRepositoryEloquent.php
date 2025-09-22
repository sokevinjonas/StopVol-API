<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domains\OTP\Entities\OtpCode;
use App\Domains\OTP\Repositories\OtpRepository;

class OtpRepositoryEloquent implements OtpRepository
{
    public function findById(string $id): ?OtpCode
    {
        return OtpCode::find($id);
    }

    public function create(array $data): OtpCode
    {
        return OtpCode::create($data);
    }

    public function update(OtpCode $otpCode, array $data): OtpCode
    {
        $otpCode->update($data);
        return $otpCode->fresh();
    }

    public function delete(OtpCode $otpCode): bool
    {
        return $otpCode->delete();
    }

    public function findByPhone(string $phone): array
    {
        return OtpCode::where('phone', $phone)
                     ->orderBy('created_at', 'desc')
                     ->get()
                     ->toArray();
    }

    public function findValidByPhone(string $phone): ?OtpCode
    {
        return OtpCode::where('phone', $phone)
                     ->where('used', false)
                     ->where('expires_at', '>', now())
                     ->orderBy('created_at', 'desc')
                     ->first();
    }

    public function findLatestByPhone(string $phone): ?OtpCode
    {
        return OtpCode::where('phone', $phone)
                     ->orderBy('created_at', 'desc')
                     ->first();
    }

    public function findExpiredCodes(): array
    {
        return OtpCode::where('expires_at', '<', now())
                     ->get()
                     ->toArray();
    }

    public function findUsedCodes(): array
    {
        return OtpCode::where('used', true)
                     ->get()
                     ->toArray();
    }

    public function countRecentByPhone(string $phone, int $minutes = 60): int
    {
        return OtpCode::where('phone', $phone)
                     ->where('created_at', '>=', now()->subMinutes($minutes))
                     ->count();
    }

    public function deleteExpiredCodes(): int
    {
        return OtpCode::where('expires_at', '<', now())->delete();
    }

    public function deleteUsedCodes(): int
    {
        return OtpCode::where('used', true)->delete();
    }

    public function findByPhoneAndCode(string $phone, string $code): ?OtpCode
    {
        return OtpCode::where('phone', $phone)
                     ->where('code', $code)
                     ->orderBy('created_at', 'desc')
                     ->first();
    }
}
