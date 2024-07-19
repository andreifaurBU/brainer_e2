<?php

namespace Core\Alerts\Application\Get;

use Core\Alerts\Application\Exceptions\GetAllAlertsByServiceException;
use Core\Alerts\Domain\Repositories\AlertRepository;

class GetAllAlertsByService
{
    public function __construct(
        private AlertRepository $alertRepository
    ) {
    }

    public function __invoke(int $service_id): array
    {
        try {
            return $this->alertRepository->getAlertsByService($service_id);
        } catch (\Throwable $th) {
            throw new GetAllAlertsByServiceException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }
}