<?php

namespace App\Models;

use App\Models\Declaration;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class DeclarationImage extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'declaration_id', 'document_type', 'type', 'path'
    ];

    protected static function booted()
    {
        static::creating(function ($i) {
            if (empty($i->id)) $i->id = (string) Str::uuid();
        });
    }

    // Relation avec la déclaration
    public function declaration()
    {
        return $this->belongsTo(Declaration::class);
    }
}
