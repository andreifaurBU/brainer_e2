<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Builder\BookingsNoReadingsBuilder;
use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Create\SaveAlert;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;
use Core\Alerts\Application\Update\UpdateAlert;
use Core\Alerts\Domain\ValueObjects\Type;

class ProcessBookingsNoReadings
{
    public function __construct(
        protected BookingsNoReadingsBuilder $bookingsNoReadingsBuilder,
        protected UpdateAlert $updateAlert,
        protected GetAlertByServiceAndType $getAlertByServiceAndType,
        protected SaveAlert $saveAlert
    )
    {
        
    }

    public function __invoke(array $params): bool
    {
        try {
            $alert = $this->getAlertByServiceAndType->__invoke((int) $params['service_id'], Type::BOOKINGS_NO_READINGS);
            if (empty($alert)) {
                $alert = $this->bookingsNoReadingsBuilder->__invoke([
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