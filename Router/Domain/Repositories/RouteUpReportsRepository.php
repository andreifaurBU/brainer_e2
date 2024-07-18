<?php

namespace Core\Router\Domain\Repositories;

interface RouteUpReportsRepository
{
    public function getReportGoing(array $route_codes,
        string $site,
        string $arrival_time_from,
        string $arrival_time_to,
        string $date_from,
        string $date_to
    );

    public function getReportReturn(array $route_codes,
        string $site,
        string $arrival_time_from,
        string $arrival_time_to,
        string $date_from,
        string $date_to
    );
}
