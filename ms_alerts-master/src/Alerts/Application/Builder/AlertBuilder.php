<?php

namespace Core\Alerts\Application\Builder;

use Core\Alerts\Domain\Alert;
use Core\Alerts\Application\Exceptions\AlertBuilderException;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Result;
use Core\Alerts\Domain\ValueObjects\Status;
use DateTime;

class AlertBuilder
{
    public function __invoke(string $type, array $data): Alert
    {
        try {
            // [WIP] Right now, we have only No Play alert and we don't need to check what
            // kind of alert is represented by data.
            // We should also change the Priority, Status and Results.
            return $type::create(
                $data['id'] ?? null,
                $data['service_id'] ?? null,
                $data['external_id'],
                $data['external_type'],
                Priority::createHigh(),
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