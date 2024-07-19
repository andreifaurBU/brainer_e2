<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;
use Core\Alerts\Application\Update\UpdateAlert;
use Core\Alerts\Domain\DelayAlert;
use Core\Alerts\Domain\ValueObjects\Type;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Status;
use Core\Alerts\Domain\ValueObjects\Result;
use DateTime;

class ProcessNoDelay
{
    public function __construct(
        protected UpdateAlert $updateAlert,
        protected GetAlertByServiceAndType $getAlertByServiceAndType
    )
    {
        
    }

    public function __invoke(array $params): bool
    {
        try {
            $previous_alerts = $this->getAlertByServiceAndType->__invoke((int) $params['service_id'], Type::DELAY);

            if (count($previous_alerts) >= 1) {
                foreach ($previous_alerts as $previous_alert) {
                    if ($previous_alert['status'] != Status::CLOSED) {
                        $alert = DelayAlert::create(
                            $previous_alert['id'],
                            $previous_alert['service_id'],
                            $previous_alert['external_id'],
                            $previous_alert['external_type'],
                            new Priority($previous_alert['priority']),
                            Status::createClosed(),
                            Result::createSolved(),
                            $previous_alert['alert_fact'],
                            new DateTime($previous_alert['created_at']),
                            new DateTime(),
                            "No delay",
                            "No delay",
                            isset($previous_alert['more_info']) ? $previous_alert['more_info'] : null
                        );
                        $this->updateAlert->__invoke($alert);
                    }
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
}