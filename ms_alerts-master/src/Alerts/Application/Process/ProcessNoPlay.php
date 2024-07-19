<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Builder\NoPlayBuilder;
use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Create\SaveAlert;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;
use Core\Alerts\Application\Update\UpdateAlert;
use Core\Alerts\Domain\NoPlayAlert;
use Core\Alerts\Domain\ValueObjects\Type;
use DateTime;

class ProcessNoPlay
{
    public function __construct(
        protected NoPlayBuilder $noPlayBuilder,
        protected UpdateAlert $updateAlert,
        protected GetAlertByServiceAndType $getAlertByServiceAndType,
        protected SaveAlert $saveAlert
    )
    {
        
    }

    public function __invoke(array $params): bool
    {
        try {
            $alert = $this->getAlertByServiceAndType->__invoke((int) $params['service_id'], Type::NO_PLAY);
            if (empty($alert)) {
                $alert = $this->noPlayBuilder->__invoke([
                    'service_id' => $params['service_id'],
                    'external_id' => $params['service_id'],
                    'external_type' => 'service',
                    'fact' => '',
                    'closed_criteria' => '',
                    'solved_criteria' => ''
                ]);
                $this->saveAlert->__invoke($alert);
            }
            return true;
        } catch (\Throwable $th) {
            throw new ProcessJobException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }
}