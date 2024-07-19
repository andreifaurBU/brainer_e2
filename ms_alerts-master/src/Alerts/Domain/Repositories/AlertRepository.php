<?php

namespace Core\Alerts\Domain\Repositories;

use Core\Alerts\Domain\Alert;

interface AlertRepository
{
    public function saveAlert(Alert $alert): bool;
    public function updateAlert(Alert $alert): bool;
    public function deleteAlert(Alert $alert): bool;
    public function getByServiceAndType(int $service_id, int $type): array;
    public function getServicesWithAlerts(array $filters): array;
    public function getAlertsByService(int $service_id): array;
}