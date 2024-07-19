<?php

namespace Core\Alerts\Domain\ValueObjects;

class Category
{
    const PLANNING = 1;
    const APP = 2;
    const ROUTE = 3;
    const SERVICE = 4;
    const DRIVER = 5;

    public function __construct(
        private int $value
    ) {
        if (
            $value !== self::PLANNING &&
            $value !== self::APP &&
            $value !== self::ROUTE &&
            $value !== self::SERVICE &&
            $value !== self::DRIVER
        ) {
            throw new \InvalidArgumentException('Valor de categoría no válido');
        }

        $this->value = $value;
    }

    public static function createDriver(): self
    {
        return new self(self::DRIVER);
    }

    public static function createService(): self
    {
        return new self(self::SERVICE);
    }

    public static function createPlanning(): self
    {
        return new self(self::PLANNING);
    }

    public static function createApp(): self
    {
        return new self(self::APP);
    }

    public static function createRoute(): self
    {
        return new self(self::ROUTE);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isDriver(): bool
    {
        return $this->value === self::DRIVER;
    }

    public function isService(): bool
    {
        return $this->value === self::SERVICE;
    }

    public function isPlanning(): bool
    {
        return $this->value === self::PLANNING;
    }

    public function isApp(): bool
    {
        return $this->value === self::APP;
    }

    public function isRoute(): bool
    {
        return $this->value === self::ROUTE;
    }
}
