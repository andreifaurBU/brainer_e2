<?php

namespace Core\Alerts\Application\Builder;

use Core\Alerts\Application\Exceptions\AlertBuilderException;
use Core\Alerts\Domain\NoStopAlert;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Result;
use Core\Alerts\Domain\ValueObjects\Status;
use DateTime;

class NoStopBuilder
{
    public function __invoke(array $data): NoStopAlert
    {
        try {
            return NoStopAlert::create(
                $data['id'] ?? null,
                $data['service_id'] ?? null,
                $data['external_id'],
                $data['external_type'],
                Priority::createMedium(),
                Status::createPending(),
                Result::createUnsolved(),
                $data['fact'],
                new DateTime(),
                new DateTime(),
                $data['closed_criteria'],
                $data['solved_criteria'],
                $data['more_info'] ?? null,
            );
        } catch (\Throwable $th) {
            throw new AlertBuilderException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }
}