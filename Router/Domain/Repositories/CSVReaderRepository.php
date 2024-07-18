<?php

namespace Core\Router\Domain\Repositories;

interface CSVReaderRepository
{
    public function load(string $file, string $delimiter);
    public function loadFromContent(string $content, string $delimiter);
    public function getHeaders(): array;
    public function getRows(): array;
}
