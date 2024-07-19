<?php

namespace Core\Alerts\Application\Update;

use Core\Alerts\Application\Exceptions\UpdateAlertException;
use Core\Alerts\Domain\Repositories\AlertRepository;
use Core\Alerts\Domain\Alert;

class UpdateAlert
{
    public function __construct(
        private AlertRepository $alertRepository
    ) {
    }

    public function __invoke(Alert $alert): bool
    {
        try {
            return $this->alertRepository->updateAlert($alert);
        } catch (\Throwable $th) {
            throw new UpdateAlertException(
                $th->getMessage(),
                $th->getCode()
            );
        }
        return false;
    }
}