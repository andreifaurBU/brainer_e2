<?php

namespace Core\Alerts\Domain;

use Core\Alerts\Domain\ValueObjects\Category;
use Core\Alerts\Domain\ValueObjects\Moment;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Result;
use Core\Alerts\Domain\ValueObjects\Status;
use Core\Alerts\Domain\ValueObjects\Type;
use DateTime;

class NoPlayAlert extends Alert
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
        string $solved_criteria
    ): NoPlayAlert
    {
        return new self(
            $id,
            $service_id,
            $external_id,
            $external_type,
            $priority,
            $status,
            $result,
            Category::createApp(),
            Type::createNoPlay(),
            $fact,
            $created_at,
            $modified_at,
            Moment::createPreLive(),
            $closed_criteria,
            $solved_criteria
        );
    }

}