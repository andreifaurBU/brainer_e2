<?php

namespace Core\Alerts\Domain\ValueObjects;

class Status
{
    const PENDING = 1;
    const WORKING = 2;
    const CLOSED = 3;
    const CANCELLED = 4;

    public function __construct(
        private int $value
    ) {
        if (
            $value !== self::PENDING &&
            $value !== self::WORKING &&
            $value !== self::CLOSED &&
            $value !== self::CANCELLED
        ) {
            throw new \InvalidArgumentException('Valor de estado no válido');
        }

        $this->value = $value;
    }

    public static function createPending(): self
    {
        return new self(self::PENDING);
    }

    public static function createWorking(): self
    {
        return new self(self::WORKING);
    }

    public static function createClosed(): self
    {
        return new self(self::CLOSED);
    }

    public static function createCancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isWorking(): bool
    {
        return $this->value === self::WORKING;
    }

    public function isClosed(): bool
    {
        return $this->value === self::CLOSED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }
}