<?php

namespace App\Domains\Declaration\ValueObjects;

class PlateNumber
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = strtoupper(trim($value));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(PlateNumber $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(string $value): void
    {
        $value = trim($value);
        
        if (empty($value)) {
            throw new \InvalidArgumentException('Plate number cannot be empty');
        }

        if (strlen($value) < 2) {
            throw new \InvalidArgumentException('Plate number is too short');
        }

        if (strlen($value) > 15) {
            throw new \InvalidArgumentException('Plate number is too long');
        }

        // Basic format validation - can be extended based on country-specific rules
        if (!preg_match('/^[A-Z0-9\-\s]+$/i', $value)) {
            throw new \InvalidArgumentException('Plate number contains invalid characters');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['value']);
    }
}
