<?php

namespace Core\Alerts\Domain\ValueObjects;

class Result
{

    public function __construct(
        private bool $value
    ) {
        $this->value = $value;
    }

    public static function createSolved(): self
    {
        return new self(true);
    }

    public static function createUnsolved(): self
    {
        return new self(false);
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function isSolved(): bool
    {
        return $this->value === true;
    }

    public function isUnsolved(): bool
    {
        return $this->value === false;
    }
}

