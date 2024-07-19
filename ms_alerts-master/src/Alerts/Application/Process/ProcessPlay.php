<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Builder\AlertBuilder;
use Core\Alerts\Application\Create\SaveAlert;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;
use Core\Alerts\Application\Update\UpdateAlert;
use Core\Alerts\Domain\NoPlayAlert;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Result;
use Core\Alerts\Domain\ValueObjects\Status;
use Core\Alerts\Domain\ValueObjects\Type;
use DateTime;

class ProcessPlay
{
    public function __construct(
        protected AlertBuilder $alertBuilder,
        protected UpdateAlert $updateAlert,
        protected GetAlertByServiceAndType $getAlertByServiceAndType,
        protected SaveAlert $saveAlert
    )
    {
        
    }

    public function __invoke(array $params): bool
    {
        try {   
            $previous_alert = $this->getAlertByServiceAndType->__invoke((int) $params['service_id'], Type::NO_PLAY);
            if (count($previous_alert) >= 1) {
                $alert = NoPlayAlert::create(
                    $previous_alert[0]['id'],
                    $previous_alert[0]['service_id'],
                    $previous_alert[0]['external_id'],
                    $previous_alert[0]['external_type'],
                    new Priority($previous_alert[0]['priority']),
                    Status::createClosed(),
                    Result::createSolved(),
                    $previous_alert[0]['alert_fact'],
                    new DateTime($previous_alert[0]['created_at']),
                    new DateTime(),
                    "Play pressed",
                    "Play pressed"
                );
                $this->updateAlert->__invoke($alert);
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