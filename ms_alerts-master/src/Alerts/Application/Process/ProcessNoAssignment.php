<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Builder\NoAssignmentBuilder;
use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Create\SaveAlert;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;
use Core\Alerts\Application\Update\UpdateAlert;
use Core\Alerts\Domain\NoAssignmentAlert;
use Core\Alerts\Domain\ValueObjects\Type;

class ProcessNoAssignment
{
    public function __construct(
        protected NoAssignmentBuilder $noAssignmentBuilder,
        protected UpdateAlert $updateAlert,
        protected GetAlertByServiceAndType $getAlertByServiceAndType,
        protected SaveAlert $saveAlert
    )
    {
        
    }

    public function __invoke(array $params): bool
    {
        try {
            $alert = $this->getAlertByServiceAndType->__invoke((int) $params['service_id'], Type::NO_ASSIGNMENT);
            if (empty($alert)) {
                $this->saveAlert->__invoke(
                    $this->createNewNoAssignmentAlert($params)
                );
            }
            return true;
        } catch (\Throwable $th) {
            throw new ProcessJobException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }

    private function createNewNoAssignmentAlert($params): NoAssignmentAlert 
    {
        if (isset($params['more_info'])) {
            $more_info = json_encode($params['more_info'], true);
        } else {
            $more_info = null;
        }

        return $this->noAssignmentBuilder->__invoke([
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