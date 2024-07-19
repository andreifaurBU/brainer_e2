<?php

namespace Core\Alerts\Application\Create;

use Core\Alerts\Application\Exceptions\SaveAlertException;
use Core\Alerts\Domain\Repositories\AlertRepository;
use Core\Alerts\Domain\Alert;

class SaveAlert
{
    public function __construct(
        private AlertRepository $alertRepository
    ) {
    }

    public function __invoke(Alert $alert): bool
    {
        try {
            return $this->alertRepository->saveAlert($alert);
        } catch (\Throwable $th) {
            throw new SaveAlertException(
                $th->getMessage(),
                $th->getCode()
            );
        }
        return false;
    }
}