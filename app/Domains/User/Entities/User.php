<?php

namespace App\Domains\User\Entities;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'phone', 'role', 'entity_id', 'profile_picture',
        'id_card_front', 'id_card_back', 'id_type', 'city', 'district',
        'profile_status', 'phone_verified_at'
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
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
    public function isProfileComplete(): bool
    {
        return !empty($this->name) 
            && !empty($this->profile_picture)
            && !empty($this->id_card_front)
            && !empty($this->city)
            && !empty($this->district);
    }

    public function isProfileValidated(): bool
    {
        return $this->profile_status === 'validated';
    }

    public function canCreateDeclaration(): bool
    {
        return $this->isProfileValidated();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'entity_admin';
    }

    public function isCitizen(): bool
    {
        return $this->role === 'citizen';
    }

    public function completeProfile(array $data): void
    {
        $this->fill($data);
        $this->profile_status = 'pending_validation';
        $this->save();
    }

    public function validateProfile(): void
    {
        $this->profile_status = 'validated';
        $this->save();
    }

    public function verifyPhone(): void
    {
        $this->phone_verified_at = now();
        $this->save();
    }
}
