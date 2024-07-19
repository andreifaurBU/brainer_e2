<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Builder\DriversAlertBuilder;
use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Create\SaveAlert;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;
use Core\Alerts\Application\Update\UpdateAlert;
use Core\Alerts\Domain\DriversAlert;
use Core\Alerts\Domain\ValueObjects\Type;
use DateTime;

class ProcessDriversAlert
{
    public function __construct(
        protected DriversAlertBuilder $driversAlertBuilder,
        protected UpdateAlert $updateAlert,
        protected GetAlertByServiceAndType $getAlertByServiceAndType,
        protected SaveAlert $saveAlert
    )
    {
        
    }

    public function __invoke(array $params): bool
    {
        try {
            $this->saveAlert->__invoke(
                $this->createNewDriversAlert($params)
            );
            return true;
        } catch (\Throwable $th) {
            throw new ProcessJobException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }

    private function createNewDriversAlert($params): DriversAlert 
    {
        if (isset($params['more_info'])) {
            $more_info = json_encode($params['more_info'], true);
        } else {
            $more_info = null;
        }

        return $this->driversAlertBuilder->__invoke([
            'service_id' => $params['service_id'],
            'external_id' => $params['service_id'],
            'external_type' => 'service',
            'fact' => '',
            'closed_criteria' => '',
            'solved_criteria' => '',
            'more_info' => $more_info
        ]);
    }


}