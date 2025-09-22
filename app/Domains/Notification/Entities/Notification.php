<?php

namespace App\Domains\Notification\Entities;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'declaration_id', 'admin_id', 'message', 'channel', 'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
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
    public function isSms(): bool
    {
        return $this->channel === 'sms';
    }

    public function isApp(): bool
    {
        return $this->channel === 'app';
    }

    public function isSent(): bool
    {
        return !is_null($this->sent_at);
    }

    public function isPending(): bool
    {
        return is_null($this->sent_at);
    }

    public function markAsSent(): void
    {
        $this->sent_at = now();
        $this->save();
    }

    public function getChannelName(): string
    {
        return match($this->channel) {
            'sms' => 'SMS',
            'app' => 'Application',
            default => 'Unknown'
        };
    }

    public function canBeResent(): bool
    {
        // Allow resending if not sent or sent more than 1 hour ago
        return $this->isPending() || 
               ($this->isSent() && $this->sent_at->diffInHours(now()) > 1);
    }

    public function getFormattedMessage(): string
    {
        return $this->message;
    }

    public static function createSmsNotification(
        string $declarationId, 
        string $message, 
        ?string $adminId = null
    ): self {
        return new self([
            'declaration_id' => $declarationId,
            'admin_id' => $adminId,
            'message' => $message,
            'channel' => 'sms'
        ]);
    }

    public static function createAppNotification(
        string $declarationId, 
        string $message, 
        ?string $adminId = null
    ): self {
        return new self([
            'declaration_id' => $declarationId,
            'admin_id' => $adminId,
            'message' => $message,
            'channel' => 'app'
        ]);
    }
}
