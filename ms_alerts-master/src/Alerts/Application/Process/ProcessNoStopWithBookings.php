<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Builder\NoStopBuilder;
use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Create\SaveAlert;
use Core\Alerts\Application\Update\UpdateAlert;

class ProcessNoStopWithBookings
{
    public function __construct(
        protected NoStopBuilder $noStopBuilder,
        protected UpdateAlert $updateAlert,
        protected SaveAlert $saveAlert
    )
    {
        
    }

    public function __invoke(array $params): bool
    {
        try {

            if (isset($params['more_info'])) {
                $more_info = json_encode($params['more_info'], true);
            } else {
                $more_info = null;
            }

            $alert = $this->noStopBuilder->__invoke([
                'service_id' => $params['service_id'],
                'external_id' => $params['service_id'],
                'external_type' => 'service',
                'fact' => '',
                'closed_criteria' => '',
                'solved_criteria' => '',
                'more_info' => $more_info,
            ]);

            $this->saveAlert->__invoke($alert);
            return true;
        } catch (\Throwable $th) {
            throw new ProcessJobException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }
}