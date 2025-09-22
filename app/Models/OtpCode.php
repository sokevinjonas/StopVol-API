<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id','phone','code','used','expires_at'
    ];

    protected static function booted()
    {
        static::creating(function ($o) {
            if (empty($o->id)) $o->id = (string) Str::uuid();
        });
    }
}
