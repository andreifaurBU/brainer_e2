<?php

namespace Core\Alerts\Domain\ValueObjects;

class Priority
{
    const HIGH = 3;
    const MEDIUM = 2;
    const LOW = 1;

    public function __construct(
        private int $value
    ) {
        if ($value !== self::HIGH && $value !== self::MEDIUM && $value !== self::LOW) {
            throw new \InvalidArgumentException('Valor de prioridad no válido');
        }

        $this->value = $value;
    }

    public static function createHigh(): self
    {
        return new self(self::HIGH);
    }

    public static function createMedium(): self
    {
        return new self(self::MEDIUM);
    }

    public static function createLow(): self
    {
        return new self(self::LOW);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isHigh(): bool
    {
        return $this->value === self::HIGH;
    }

    public function isMedium(): bool
    {
        return $this->value === self::MEDIUM;
    }

    public function isLow(): bool
    {
        return $this->value === self::LOW;
    }
}


?>
