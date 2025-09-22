<?php

namespace App\Models;

use App\Models\User;
use App\Models\Declaration;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id','declaration_id','admin_id','message','channel','sent_at'
    ];

    protected static function booted()
    {
        static::creating(function ($n) {
            if (empty($n->id)) $n->id = (string) Str::uuid();
        });
    }

    // Notification liée à une déclaration
    public function declaration()
    {
        return $this->belongsTo(Declaration::class);
    }

    // Admin qui a envoyé la notification
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Propriétaire du véhicule (via la déclaration)
    public function user()
    {
        return $this->hasOneThrough(User::class, Declaration::class, 'id', 'id', 'declaration_id', 'user_id');
    }
}
