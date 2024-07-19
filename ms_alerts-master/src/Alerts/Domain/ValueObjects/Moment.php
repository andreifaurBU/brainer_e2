<?php

namespace Core\Alerts\Domain\ValueObjects;

class Moment
{
    const PAST = 1;
    const PRE_LIVE = 2;
    const LIVE = 3;
    const FUTURE = 4;

    public function __construct(
        private int $value
    ) {
        if (
            $value !== self::PAST &&
            $value !== self::PRE_LIVE &&
            $value !== self::LIVE &&
            $value !== self::FUTURE
        ) {
            throw new \InvalidArgumentException('Valor de momento no válido');
        }

        $this->value = $value;
    }

    public static function createPast(): self
    {
        return new self(self::PAST);
    }

    public static function createPreLive(): self
    {
        return new self(self::PRE_LIVE);
    }

    public static function createLive(): self
    {
        return new self(self::LIVE);
    }

    public static function createFuture(): self
    {
        return new self(self::FUTURE);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isPast(): bool
    {
        return $this->value === self::PAST;
    }

    public function isPreLive(): bool
    {
        return $this->value === self::PRE_LIVE;
    }

    public function isLive(): bool
    {
        return $this->value === self::LIVE;
    }

    public function isFuture(): bool
    {
        return $this->value === self::FUTURE;
    }
}
