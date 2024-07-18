<?php

namespace Core\Router\Infrastructure\Reader;

use Core\Router\Domain\Repositories\CSVReaderRepository;
use League\Csv\Reader;

class CSVReader implements CSVReaderRepository
{

    private Reader $csv;

    public function load(string $file, string $delimiter = ";")
    {
        $this->csv = Reader::createFromPath($file, 'r')->setHeaderOffset(0);
        $this->csv->setDelimiter($delimiter);
    }

    public function loadFromContent(string $content, string $delimiter = ";")
    {
        $this->csv = Reader::createFromString($content)->setHeaderOffset(0);
        $this->csv->setDelimiter($delimiter);
    }

    public function getHeaders(): array
    {
        return $this->csv->getHeader();
    }

    public function getRows(): array
    {
        $list = [];
        foreach ($this->csv->getRecords() as $record) {
            $list[] = $record;
        }
        return $list;
    }

}
