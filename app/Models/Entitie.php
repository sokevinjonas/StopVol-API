<?php

namespace App\Models;

use App\Models\User;
use App\Models\Declaration;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Entitie extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','name','address','phone','manager_name'];

    protected static function booted()
    {
        static::creating(function ($e) {
            if (empty($e->id)) $e->id = (string) Str::uuid();
        });
    }

    // Les admins liés à cette entité
    public function admins()
    {
        return $this->hasMany(User::class, 'entity_id')->where('role', 'entity_admin');
    }

    // Optionnel : toutes les déclarations gérées par cette entité (via admins)
    public function declarations()
    {
        return $this->hasManyThrough(Declaration::class, User::class, 'entity_id', 'user_id', 'id', 'id');
    }
}
