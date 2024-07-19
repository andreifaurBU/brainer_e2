<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Builder\AlertBuilder;
use Core\Alerts\Application\Create\SaveAlert;
use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;

class ProcessJob
{

    public function __construct(
        protected ProcessNoPlay $processNoPlay,
        protected GetAlertByServiceAndType $getAlertByServiceAndType,
        protected AlertBuilder $alertBuilder,
        protected SaveAlert $saveAlert,
        protected ProcessPlay $processPlay,
        protected ProcessServiceFinished $processServiceFinished,
        protected ProcessBookingsNoReadings $processBookingNoReadings,
        protected ProcessReadings $processReadings,
        protected ProcessNoStopWithBookings $processNoStopWithBookings,
        protected ProcessDelay $processDelay,
        protected ProcessNoDelay $processNoDelay,
        protected ProcessDriversAlert $processDriversAlert,
        protected ProcessNoAssignment $processNoAssignment,
        protected ProcessAssignment $processAssignment
    ) {
    }

    public function __invoke(array $params): bool
    {
        try {
            if (!isset($params['action']) || !isset($params['service_id'])) {
                return false;
            }

            switch ($params['action']) {
                case 'no_play':
                    return $this->processNoPlay->__invoke($params);
                case 'play':
                    return $this->processPlay->__invoke($params);
                case 'service_finished':
                    return $this->processServiceFinished->__invoke($params);
                case 'bookings_no_readings':
                    return $this->processBookingNoReadings->__invoke($params);
                case 'readings':
                    return $this->processReadings->__invoke($params);
                case 'skipped_stops_with_bookings':
                    return $this->processNoStopWithBookings->__invoke($params);
                case 'delay':
                    return $this->processDelay->__invoke($params);
                case 'no_delay':
                    return $this->processNoDelay->__invoke($params);
                case 'drivers_alert':
                    return $this->processDriversAlert->__invoke($params);
                case 'no_assignment':
                    return $this->processNoAssignment->__invoke($params);
                case 'assignment':
                    return $this->processAssignment->__invoke($params);
            }

            return false;
        } catch (\Throwable $th) {
            throw new ProcessJobException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }

}