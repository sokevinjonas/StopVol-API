<?php

namespace App\Domains\OTP\Entities;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'phone', 'code', 'used', 'expires_at'
    ];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // Business methods
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->used && !$this->isExpired();
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markAsUsed(): void
    {
        $this->used = true;
        $this->save();
    }

    public function verify(string $code): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->code !== $code) {
            return false;
        }

        $this->markAsUsed();
        return true;
    }

    public function getRemainingTime(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return $this->expires_at->diffInSeconds(now());
    }

    public function getRemainingTimeFormatted(): string
    {
        $seconds = $this->getRemainingTime();
        
        if ($seconds <= 0) {
            return 'Expiré';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return sprintf('%d min %d sec', $minutes, $remainingSeconds);
        }

        return sprintf('%d sec', $remainingSeconds);
    }

    public static function generateCode(int $length = 6): string
    {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    public static function createForPhone(string $phone, int $expirationMinutes = 10): self
    {
        return new self([
            'phone' => $phone,
            'code' => self::generateCode(),
            'used' => false,
            'expires_at' => now()->addMinutes($expirationMinutes)
        ]);
    }
}
