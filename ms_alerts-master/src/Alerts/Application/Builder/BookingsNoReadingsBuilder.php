<?php

namespace Core\Alerts\Application\Builder;

use Core\Alerts\Application\Exceptions\AlertBuilderException;
use Core\Alerts\Domain\BookingsNoReadingsAlert;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Result;
use Core\Alerts\Domain\ValueObjects\Status;
use DateTime;

class BookingsNoReadingsBuilder
{
    public function __invoke(array $data): BookingsNoReadingsAlert
    {
        try {
            return BookingsNoReadingsAlert::create(
                $data['id'] ?? null,
                $data['service_id'] ?? null,
                $data['external_id'],
                $data['external_type'],
                Priority::createLow(),
                Status::createPending(),
                Result::createUnsolved(),
                $data['fact'],
                new DateTime(),
                new DateTime(),
                $data['closed_criteria'],
                $data['solved_criteria']
            );
        } catch (\Throwable $th) {
            throw new AlertBuilderException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }
}