<?php

namespace Core\Alerts\Application\Get;

use Core\Alerts\Application\Exceptions\GetAlertByServiceAndTypeException;
use Core\Alerts\Domain\Repositories\AlertRepository;
use Core\Alerts\Domain\Alert;
use Core\Alerts\Domain\ValueObjects\Type;

class GetAlertByServiceAndType
{
    public function __construct(
        private AlertRepository $alertRepository
    ) {
    }

    public function __invoke($service_id, $type): array
    {
        try {
            return $this->alertRepository->getByServiceAndType($service_id, $type);
        } catch (\Throwable $th) {
            throw new GetAlertByServiceAndTypeException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }
}