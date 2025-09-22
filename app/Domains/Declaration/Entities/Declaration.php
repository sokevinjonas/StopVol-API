<?php

namespace App\Domains\Declaration\Entities;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Declaration extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'user_id', 'plate_number', 'chassis_number', 'card_number',
        'brand', 'model', 'color', 'pictures', 'theft_date', 'theft_location', 'status'
    ];

    protected $casts = [
        'pictures' => 'array',
        'theft_date' => 'date',
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
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFound(): bool
    {
        return $this->status === 'found';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function markAsFound(): void
    {
        $this->status = 'found';
        $this->save();
    }

    public function markAsClosed(): void
    {
        $this->status = 'closed';
        $this->save();
    }

    public function hasPlateNumber(): bool
    {
        return !empty($this->plate_number);
    }

    public function hasChassisNumber(): bool
    {
        return !empty($this->chassis_number);
    }

    public function getSearchableIdentifiers(): array
    {
        $identifiers = [];
        
        if ($this->hasPlateNumber()) {
            $identifiers['plate_number'] = $this->plate_number;
        }
        
        if ($this->hasChassisNumber()) {
            $identifiers['chassis_number'] = $this->chassis_number;
        }
        
        if (!empty($this->card_number)) {
            $identifiers['card_number'] = $this->card_number;
        }
        
        return $identifiers;
    }

    public function addPicture(string $picturePath): void
    {
        $pictures = $this->pictures ?? [];
        $pictures[] = $picturePath;
        $this->pictures = $pictures;
        $this->save();
    }

    public function removePicture(string $picturePath): void
    {
        $pictures = $this->pictures ?? [];
        $pictures = array_filter($pictures, fn($pic) => $pic !== $picturePath);
        $this->pictures = array_values($pictures);
        $this->save();
    }

    public function getVehicleInfo(): array
    {
        return [
            'brand' => $this->brand,
            'model' => $this->model,
            'color' => $this->color,
            'plate_number' => $this->plate_number,
            'chassis_number' => $this->chassis_number,
            'card_number' => $this->card_number,
        ];
    }

    public function getTheftInfo(): array
    {
        return [
            'theft_date' => $this->theft_date,
            'theft_location' => $this->theft_location,
        ];
    }
}
