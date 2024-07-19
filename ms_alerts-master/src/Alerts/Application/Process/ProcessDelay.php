<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Builder\DelayBuilder;
use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Create\SaveAlert;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;
use Core\Alerts\Application\Update\UpdateAlert;
use Core\Alerts\Domain\DelayAlert;
use Core\Alerts\Domain\ValueObjects\Type;
use DateTime;

class ProcessDelay
{
    public function __construct(
        protected DelayBuilder $delayBuilder,
        protected UpdateAlert $updateAlert,
        protected GetAlertByServiceAndType $getAlertByServiceAndType,
        protected SaveAlert $saveAlert
    )
    {
        
    }

    public function __invoke(array $params): bool
    {
        try {
            $alerts = $this->getAlertByServiceAndType->__invoke((int) $params['service_id'], Type::DELAY);
            if (empty($alerts)) {
                $this->saveAlert->__invoke(
                    $this->createNewDelayAlert($params)
                );
            } else {
                $createAlert = true;
                foreach ($alerts as $alert) {
                    $alert = DelayAlert::createFromArray($alert);
                    if (
                        !$alert->getStatus()->isClosed() &&
                        !$alert->getResult()->isSolved()
                    ) {
                        $createAlert = false;
                    }
                }
                if ($createAlert) {
                    $this->saveAlert->__invoke(
                        $this->createNewDelayAlert($params)
                    );
                }
            }
            return true;
        } catch (\Throwable $th) {
            throw new ProcessJobException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }

    private function createNewDelayAlert($params): DelayAlert 
    {
        if (isset($params['more_info'])) {
            $more_info = json_encode($params['more_info'], true);
        } else {
            $more_info = null;
        }

        return $this->delayBuilder->__invoke([
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