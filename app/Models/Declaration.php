<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\DeclarationImage;
use Illuminate\Database\Eloquent\Model;

class Declaration extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id','user_id','plate_number','chassis_number','card_number',
        'brand','model','color','pictures','theft_date','theft_location','status'
    ];

    protected $casts = [
        'pictures' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($d) {
            if (empty($d->id)) $d->id = (string) Str::uuid();
        });
    }

    // Relation avec le propriétaire (citizen)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec les images liées à cette déclaration
    public function images()
    {
        return $this->hasMany(DeclarationImage::class);
    }

    // Relation avec les notifications envoyées
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
