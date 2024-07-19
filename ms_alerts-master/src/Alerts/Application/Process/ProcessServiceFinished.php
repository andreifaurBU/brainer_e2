<?php

namespace Core\Alerts\Application\Process;

use Core\Alerts\Application\Exceptions\ProcessJobException;
use Core\Alerts\Application\Builder\AlertBuilder;
use Core\Alerts\Application\Get\GetAlertByServiceAndType;
use Core\Alerts\Application\Update\UpdateAlert;
use Core\Alerts\Domain\BookingsNoReadingsAlert;
use Core\Alerts\Domain\DelayAlert;
use Core\Alerts\Domain\NoPlayAlert;
use Core\Alerts\Domain\NoStopAlert;
use Core\Alerts\Domain\DriversAlert;
use Core\Alerts\Domain\NoAssignmentAlert;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Result;
use Core\Alerts\Domain\ValueObjects\Status;
use Core\Alerts\Domain\ValueObjects\Type;
use DateTime;

class ProcessServiceFinished
{
    public function __construct(
        protected AlertBuilder $alertBuilder,
        protected UpdateAlert $updateAlert,
        protected GetAlertByServiceAndType $getAlertByServiceAndType
    ) {
    }

    public function __invoke(array $params): bool
    {
        try {
            return  $this->checkNoPlay($params)
                    && $this->checkBookingsNoReadings($params)
                    && $this->checkNoStop($params)
                    && $this->checkDelay($params)
                    && $this->checkDriversAlert($params)
                    && $this->checkNoAssignment($params);
        } catch (\Throwable $th) {
            throw new ProcessJobException(
                $th->getMessage(),
                $th->getCode()
            );
        }
    }

    private function checkNoAssignment($params): bool
    {
        $alertsNoAssignment = $this->getAlertByServiceAndType->__invoke((int)$params['service_id'], Type::NO_ASSIGNMENT);
        if (count($alertsNoAssignment) >= 1 && $alertsNoAssignment[0]['status'] === Status::PENDING) {
            $alert = NoAssignmentAlert::create(
                $alertsNoAssignment[0]['id'],
                $alertsNoAssignment[0]['service_id'],
                $alertsNoAssignment[0]['external_id'],
                $alertsNoAssignment[0]['external_type'],
                new Priority($alertsNoAssignment[0]['priority']),
                Status::createClosed(),
                Result::createUnsolved(),
                $alertsNoAssignment[0]['alert_fact'],
                new DateTime($alertsNoAssignment[0]['created_at']),
                new DateTime(),
                "Service Finished",
                ""
            );
            $this->updateAlert->__invoke($alert);
        }
        return true;
    }

    private function checkDelay($params): bool
    {
        $alertsDelay = $this->getAlertByServiceAndType->__invoke((int)$params['service_id'], Type::DELAY);
        if (count($alertsDelay) >= 1) {
            foreach ($alertsDelay as $delayAlert) {
                if ($delayAlert['status'] === Status::PENDING) {
                    $alert = DelayAlert::create(
                        $delayAlert['id'],
                        $delayAlert['service_id'],
                        $delayAlert['external_id'],
                        $delayAlert['external_type'],
                        new Priority($delayAlert['priority']),
                        Status::createClosed(),
                        Result::createUnsolved(),
                        $delayAlert['alert_fact'],
                        new DateTime($delayAlert['created_at']),
                        new DateTime(),
                        "Service Finished",
                        "",
                        isset($delayAlert['more_info']) ? $delayAlert['more_info'] : null

                    );
                    $this->updateAlert->__invoke($alert);
                }
            }
        }
        return true;    
    }

    private function checkNoStop(array $params): bool
    {
        $alertsNoStop = $this->getAlertByServiceAndType->__invoke((int)$params['service_id'], Type::NO_STOP);
        if (count($alertsNoStop) >= 1) {
            foreach ($alertsNoStop as $stopAlert) {
                if ($stopAlert['status'] === Status::PENDING) {
                    $alert = NoStopAlert::create(
                        $stopAlert['id'],
                        $stopAlert['service_id'],
                        $stopAlert['external_id'],
                        $stopAlert['external_type'],
                        new Priority($stopAlert['priority']),
                        Status::createClosed(),
                        Result::createUnsolved(),
                        $stopAlert['alert_fact'],
                        new DateTime($stopAlert['created_at']),
                        new DateTime(),
                        "Service Finished",
                        "",
                        isset($stopAlert['more_info']) ? $stopAlert['more_info'] : null
                    );
                    $this->updateAlert->__invoke($alert);
                }
            }
        }
        return true;
    }

    private function checkNoPlay(array $params): bool
    {
        $alertNoPlay = $this->getAlertByServiceAndType->__invoke((int)$params['service_id'], Type::NO_PLAY);
        if (count($alertNoPlay) >= 1 && $alertNoPlay[0]['status'] === Status::PENDING) {
            $alert = NoPlayAlert::create(
                $alertNoPlay[0]['id'],
                $alertNoPlay[0]['service_id'],
                $alertNoPlay[0]['external_id'],
                $alertNoPlay[0]['external_type'],
                new Priority($alertNoPlay[0]['priority']),
                Status::createClosed(),
                Result::createUnsolved(),
                $alertNoPlay[0]['alert_fact'],
                new DateTime($alertNoPlay[0]['created_at']),
                new DateTime(),
                "Service Finished",
                ""
            );
            $this->updateAlert->__invoke($alert);
        }
        return true;
    }

    private function checkBookingsNoReadings(array $params): bool
    {
        $alertBookingNoReading = $this->getAlertByServiceAndType->__invoke(
            (int)$params['service_id'],
            Type::BOOKINGS_NO_READINGS
        );
        if (count($alertBookingNoReading) >= 1 && $alertBookingNoReading[0]['status'] === Status::PENDING) {
            $alert = BookingsNoReadingsAlert::create(
                $alertBookingNoReading[0]['id'],
                $alertBookingNoReading[0]['service_id'],
                $alertBookingNoReading[0]['external_id'],
                $alertBookingNoReading[0]['external_type'],
                new Priority($alertBookingNoReading[0]['priority']),
                Status::createClosed(),
                Result::createUnsolved(),
                $alertBookingNoReading[0]['alert_fact'],
                new DateTime($alertBookingNoReading[0]['created_at']),
                new DateTime(),
                "Service Finished",
                ""
            );
            $this->updateAlert->__invoke($alert);
        }
        return true;
    }

    protected function checkDriversAlert(array $params): bool
    {
        $alertsDrivers = $this->getAlertByServiceAndType->__invoke(
            (int)$params['service_id'],
            Type::DRIVERS
        );

        if (count($alertsDrivers) >= 1) {
            foreach ($alertsDrivers as $driversAlert) {
                if ($driversAlert['status'] === Status::PENDING) {
                    $alert = DriversAlert::create(
                        $driversAlert['id'],
                        $driversAlert['service_id'],
                        $driversAlert['external_id'],
                        $driversAlert['external_type'],
                        new Priority($driversAlert['priority']),
                        Status::createClosed(),
                        Result::createUnsolved(),
                        $driversAlert['alert_fact'],
                        new DateTime($driversAlert['created_at']),
                        new DateTime(),
                        "Service Finished",
                        "",
                        isset($driversAlert['more_info']) ? $driversAlert['more_info'] : null
                    );
                    $this->updateAlert->__invoke($alert);
                }
            }
        }

        return true;
    }
}