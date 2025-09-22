<?php

namespace App\Domains\OTP\Services;

use App\Domains\OTP\Entities\OtpCode;
use App\Domains\OTP\Repositories\OtpRepository;
use App\Infrastructure\Messaging\SmsSender;

class OtpService
{
    private const MAX_OTP_PER_HOUR = 3;
    private const OTP_EXPIRATION_MINUTES = 10;

    public function __construct(
        private OtpRepository $otpRepository,
        private SmsSender $smsSender
    ) {}

    public function sendOtp(string $phone): OtpCode
    {
        // Validate phone number
        $this->validatePhoneNumber($phone);

        // Check rate limiting
        $this->checkRateLimit($phone);

        // Create new OTP
        $otpCode = OtpCode::createForPhone($phone, self::OTP_EXPIRATION_MINUTES);
        $otpCode = $this->otpRepository->create($otpCode->toArray());

        // Send SMS
        $this->sendOtpSms($otpCode);

        return $otpCode;
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        $otpCode = $this->otpRepository->findByPhoneAndCode($phone, $code);

        if (!$otpCode) {
            return false;
        }

        return $otpCode->verify($code);
    }

    public function resendOtp(string $phone): OtpCode
    {
        // Find the latest OTP for this phone
        $latestOtp = $this->otpRepository->findLatestByPhone($phone);

        if (!$latestOtp) {
            throw new \InvalidArgumentException('No OTP found for this phone number');
        }

        if ($latestOtp->isValid()) {
            throw new \InvalidArgumentException('Current OTP is still valid');
        }

        // Check rate limiting
        $this->checkRateLimit($phone);

        // Create new OTP
        return $this->sendOtp($phone);
    }

    public function getValidOtp(string $phone): ?OtpCode
    {
        return $this->otpRepository->findValidByPhone($phone);
    }

    public function getRemainingTime(string $phone): int
    {
        $otpCode = $this->getValidOtp($phone);
        
        if (!$otpCode) {
            return 0;
        }

        return $otpCode->getRemainingTime();
    }

    public function canRequestNewOtp(string $phone): bool
    {
        $validOtp = $this->getValidOtp($phone);
        
        if ($validOtp) {
            return false;
        }

        $recentCount = $this->otpRepository->countRecentByPhone($phone, 60);
        
        return $recentCount < self::MAX_OTP_PER_HOUR;
    }

    public function cleanupExpiredOtps(): int
    {
        return $this->otpRepository->deleteExpiredCodes();
    }

    public function cleanupUsedOtps(): int
    {
        return $this->otpRepository->deleteUsedCodes();
    }

    public function getOtpStats(string $phone): array
    {
        $validOtp = $this->getValidOtp($phone);
        $recentCount = $this->otpRepository->countRecentByPhone($phone, 60);

        return [
            'has_valid_otp' => !is_null($validOtp),
            'remaining_time' => $validOtp ? $validOtp->getRemainingTime() : 0,
            'remaining_time_formatted' => $validOtp ? $validOtp->getRemainingTimeFormatted() : 'Aucun',
            'recent_requests' => $recentCount,
            'can_request_new' => $this->canRequestNewOtp($phone),
            'max_requests_per_hour' => self::MAX_OTP_PER_HOUR
        ];
    }

    private function validatePhoneNumber(string $phone): void
    {
        // Basic phone validation - can be enhanced based on country requirements
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (empty($phone)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        if (strlen($phone) < 8) {
            throw new \InvalidArgumentException('Phone number is too short');
        }

        if (strlen($phone) > 15) {
            throw new \InvalidArgumentException('Phone number is too long');
        }
    }

    private function checkRateLimit(string $phone): void
    {
        $recentCount = $this->otpRepository->countRecentByPhone($phone, 60);
        
        if ($recentCount >= self::MAX_OTP_PER_HOUR) {
            throw new \InvalidArgumentException(
                sprintf('Too many OTP requests. Maximum %d per hour allowed.', self::MAX_OTP_PER_HOUR)
            );
        }
    }

    private function sendOtpSms(OtpCode $otpCode): void
    {
        $message = sprintf(
            'Votre code de vérification StopVol est: %s. Ce code expire dans %d minutes.',
            $otpCode->code,
            self::OTP_EXPIRATION_MINUTES
        );

        $this->smsSender->send($otpCode->phone, $message);
    }
}
