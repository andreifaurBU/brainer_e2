<?php

namespace Core\Alerts\Domain;

use Core\Alerts\Domain\ValueObjects\Category;
use Core\Alerts\Domain\ValueObjects\Moment;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Result;
use Core\Alerts\Domain\ValueObjects\Status;
use Core\Alerts\Domain\ValueObjects\Type;
use DateTime;

class DriversAlert extends Alert
{

    public static function create(
        ?string $id,
        ?string $service_id,
        string $external_id,
        string $external_type,
        Priority $priority,
        Status $status,
        Result $result,
        string $fact,
        DateTime $created_at,
        DateTime $modified_at,
        string $closed_criteria,
        string $solved_criteria,
        ?string $more_info = null
    ): DriversAlert
    {
        return new self(
            $id,
            $service_id,
            $external_id,
            $external_type,
            $priority,
            $status,
            $result,
            Category::createDriver(),
            Type::createDriversAlert(),
            $fact,
            $created_at,
            $modified_at,
            Moment::createLive(),
            $closed_criteria,
            $solved_criteria,
            $more_info
        );
    }

    public static function createFromArray(array $alert): DriversAlert
    {
        return new self(
            $alert['id'],
            $alert['service_id'],
            $alert['external_id'],
            $alert['external_type'],
            new Priority($alert['priority']),
            new Status($alert['status']),
            new Result($alert['is_solved']),
            new Category($alert['category']),
            new Type($alert['type']),
            $alert['alert_fact'],
            new DateTime($alert['created_at']),
            new DateTime($alert['modified_at']),
            new Moment($alert['moment']),
            $alert['closed_criteria'],
            $alert['solved_criteria'],
            $alert['more_info']
        );
    }

}