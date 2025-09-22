<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Entitie;
use App\Models\Declaration;
use App\Models\Notification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [''];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) $model->id = (string) Str::uuid();
        });
    }

    // Relation avec l'entité (commissariat/gendarmerie)
    public function entity()
    {
        return $this->belongsTo(Entitie::class);
    }

    // Relation avec les déclarations qu'il a faites
    public function declarations()
    {
        return $this->hasMany(Declaration::class);
    }

    // Relation avec les notifications qu'il a reçues (via les déclarations)
    public function notifications()
    {
        return $this->hasManyThrough(Notification::class, Declaration::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
