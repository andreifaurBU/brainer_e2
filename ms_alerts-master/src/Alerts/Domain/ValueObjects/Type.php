<?php

namespace Core\Alerts\Domain\ValueObjects;

class Type
{
    const ALL_TYPES = [1,2,3,4,5,6];
    const NO_PLAY = 1;
    const BOOKINGS_NO_READINGS = 2;
    const NO_STOP = 3;
    const DELAY = 4;
    const DRIVERS = 5;
    const NO_ASSIGNMENT = 6;

    public function __construct(
        private int $value
    ) {
        if ($value < 1 || $value > 6) {
            throw new \InvalidArgumentException('Valor de tipo no válido');
        }

        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public static function getTypeName($type)
    {
        switch ($type) {
            case self::NO_PLAY:
                return 'NO_PLAY';
            case self::BOOKINGS_NO_READINGS:
                return 'BOOKINGS_NO_READINGS';
            case self::NO_STOP:
                return 'NO_STOP';
            case self::DELAY:
                return 'DELAY';
            case self::DRIVERS:
                return 'DRIVERS';
            case self::NO_ASSIGNMENT:
                return 'NO_ASSIGNMENT';
            default:
                return 'UNKNOWN';
        }
    }

    public static function createNoAssignmentAlert(): self
    {
        return new self(self::NO_ASSIGNMENT);
    }

    public static function createDriversAlert(): self
    {
        return new self(self::DRIVERS);
    }

    public static function createDelay(): self
    {
        return new self(self::DELAY);
    }

    public static function createNoPlay(): self
    {
        return new self(self::NO_PLAY);
    }

    public static function createNoStop(): self
    {
        return new self(self::NO_STOP);
    }

    public static function createBookingsNoReadings(): self
    {
        return new self(self::BOOKINGS_NO_READINGS);
    }

    public function isNoAssignmentAlert(): bool
    {
        return $this->value === self::NO_ASSIGNMENT;
    }

    public function isDriversAlert(): bool
    {
        return $this->value === self::DRIVERS;
    }

    public function isNoPlay(): bool
    {
        return $this->value === self::NO_PLAY;
    }
    
    public function isNoStop(): bool
    {
        return $this->value === self::NO_STOP;
    }

    public function isBookingNoReading(): bool
    {
        return $this->value === self::BOOKINGS_NO_READINGS;
    }

    public function isDelay(): bool
    {
        return $this->value === self::DELAY;
    }

}