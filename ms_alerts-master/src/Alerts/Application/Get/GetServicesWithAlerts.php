<?php

namespace Core\Alerts\Application\Get;

use Core\Alerts\Application\Exceptions\GetServicesWithAlertsException;
use Core\Alerts\Domain\Repositories\AlertRepository;

class GetServicesWithAlerts
{
    public function __construct(
        private AlertRepository $alertRepository
    ) {
    }

    public function __invoke(array $filters): array
    {
        try {
            return $this->alertRepository->getServicesWithAlerts($filters);
        } catch (\Throwable $th) {
            throw new GetServicesWithAlertsException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }
}