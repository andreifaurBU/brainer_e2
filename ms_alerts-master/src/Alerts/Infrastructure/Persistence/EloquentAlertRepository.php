<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Core\Alerts\Domain\Alert;
use Core\Alerts\Domain\Repositories\AlertRepository;
use Core\Alerts\Domain\ValueObjects\Status;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Type;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateInterval;
use Illuminate\Support\Facades\Log;

class EloquentAlertRepository implements AlertRepository
{
    public function saveAlert(Alert $alert): bool
    {
        try {
            AlertModel::create(
                $alert->toArray()
            );
            return true;
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function updateAlert(Alert $alert): bool
    {
        try {
            $alertModel = AlertModel::find($alert->getId());

            if ($alertModel) {
                $alertModel->update($alert->toArray());
                return true;
            }

            return false;
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function deleteAlert(Alert $alert): bool
    {
        try {
            $alertModel = AlertModel::find($alert->getId());

            if ($alertModel) {
                $alertModel->delete();
                return true;
            }

            return false;
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function getAlertsByService(int $service_id): array
    {
        try {
            $alerts = AlertModel::where('service_id', $service_id)
                ->get()
                ->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'priority' => $this->getPriorityLiteral($alert->priority),
                        'status' => $this->getStatusLiteral($alert->status),
                        'type' => $this->getTypeLiteral($alert->type),
                        'created_at' => $alert->created_at,
                        'modified_at' => $alert->modified_at,
                        'is_solved' => (bool)$alert->is_solved,
                        'more_info' => !is_null($alert->more_info) ? json_decode($alert->more_info, true) : null,
                    ];
                });

            return $alerts->toArray();
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    private function getTypeLiteral(int $type): string
    {
        switch ($type) {
            case Type::NO_PLAY:
                return 'NO_PLAY';
            case Type::BOOKINGS_NO_READINGS:
                return 'BOOKINGS_NO_READINGS';
            case Type::DELAY:
                return 'DELAY';
            case Type::NO_STOP:
                return 'NO_STOP';
            case Type::DRIVERS:
                return 'DRIVERS';
            case Type::NO_ASSIGNMENT:
                return 'NO_ASSIGNMENT';
            default:
                return 'UNKNOWN';
        }
    }


    private function getPriorityLiteral(int $priority): string
    {
        switch ($priority) {
            case Priority::HIGH:
                return 'HIGH';
            case Priority::MEDIUM:
                return 'MEDIUM';
            case Priority::LOW:
                return 'LOW';
            default:
                return 'UNKNOWN';
        }
    }

    private function getStatusLiteral(int $status): string
    {
        switch ($status) {
            case Status::PENDING:
                return 'PENDING';
            case Status::WORKING:
                return 'WORKING';
            case Status::CLOSED:
                return 'CLOSED';
            case Status::CANCELLED:
                return 'CANCELLED';
            default:
                return 'UNKNOWN';
        }
    }

    private function getFiltersForServices(array $filters): array
    {
        $countries = [];
        $businessUnits = [];
        $sites = [];
        $providers = [];
        $priority = [];
        $alertsStatus = [];
        $routes = [];
        $serviceStatus = [];


        if (isset($filters['countries'])) {
            foreach ($filters['countries'] as $country) {
                $countries[] = $country['id'];
            }
        }

        if (isset($filters['businessUnits'])) {
            foreach ($filters['businessUnits'] as $unit) {
                $businessUnits[] = $unit['id'];
            }
        }

        if (isset($filters['sites'])) {
            foreach ($filters['sites'] as $site) {
                $sites[] = $site['id'];
            }
        }

        if (isset($filters['providers'])) {
            foreach ($filters['providers'] as $provider) {
                $providers[] = $provider['id'];
            }
        }

        if (isset($filters['routeNames'])) {
            foreach ($filters['routeNames'] as $route) {
                $routes[] = $route['id'];
            }
        }

        if (isset($filters['priority'])) {
            foreach ($filters['priority'] as $priority) {
                switch ($priority['id']) {
                    case 'high':
                        $priority[] = Priority::HIGH;
                        break;
                    case 'medium':
                        $priority[] = Priority::MEDIUM;
                        break;
                    case 'low':
                        $priority[] = Priority::LOW;
                        break;
                }
            }
        }

        if (isset($filters['alertStatus'])) {
            foreach ($filters['alertStatus'] as $status) {
                switch ($status['id']) {
                    case 'pending':
                        $alertsStatus[] = Status::PENDING;
                        break;
                    case 'working':
                        $alertsStatus[] = Status::WORKING;
                        break;
                    case 'closed':
                        $alertsStatus[] = Status::CLOSED;
                        break;
                    case 'cancelled':
                        $alertsStatus[] = Status::CANCELLED;
                        break;
                }
            }
        }

        foreach ($filters as $color => $isActive) {
            if ($isActive && isset(ServiceModel::$colorStatusMapping[$color])) {
                $serviceStatus[] = ServiceModel::$colorStatusMapping[$color];
            }
        }

        return [$countries, $businessUnits, $sites, $providers, $priority, $alertsStatus, $routes, $serviceStatus];
    }


    // [WIP] We are filtering by alerts that happens in the current day, but we should filter it by services that are happening today.
    public function getServicesWithAlerts(array $filters): array
    {
        list(
            $countries, $businessUnits, $sites, $providers, $priority, $alertsStatus, $routes, $serviceStatus
            )
            = $this->getFiltersForServices($filters);

        $perPage = 20;
        $twentyFourHoursAgo = (new DateTime())->sub(new DateInterval('P2D'))->format('Y-m-d H:i:s');

        try {
            $query = AlertModel::
            with([
                'service' => function ($query) {
                    $query->select(
                        'id',
                        'external_route_id',
                        'name',
                        'notes',
                        'timestamp',
                        'arrival_timestamp',
                        'departure_timestamp',
                        'reported_departure_timestamp',
                        'reported_arrival_timestamp',
                        'status',
                        'created',
                        'modified'
                    );
                },
                'service.route' => function ($query) {
                    $query->select('title', 'id', 'primary_site', 'province_id');
                },
                'service.route.province' => function ($query) {
                    $query->select('id', 'name', 'timezone', 'country_id');
                },
                'service.route.site' => function ($query) {
                    $query->select('name', 'id');
                },
            ])
                ->
                select([
                    'service_id',
                    DB::raw('COUNT(*) AS alert_count'),
                    DB::raw(
                        "CASE
                        WHEN MAX(priority) = " . Priority::HIGH . " THEN 'HIGH'
                        WHEN MAX(priority) = " . Priority::MEDIUM . " THEN 'MEDIUM'
                        WHEN MAX(priority) = " . Priority::LOW . " THEN 'LOW'
                        ELSE 'UNKNOWN'
                    END AS max_priority"
                    ),
                    DB::raw('MAX(priority) AS raw_max_priority'),
                    DB::raw(
                        'COUNT(CASE WHEN alerts.status = ' . Status::PENDING . ' THEN 1 ELSE NULL END) AS pending_count'
                    ),
                    DB::raw(
                        'COUNT(CASE WHEN alerts.status = ' . Status::WORKING . ' THEN 1 ELSE NULL END) AS working_count'
                    ),
                    DB::raw(
                        'COUNT(CASE WHEN alerts.status = ' . Status::CLOSED . ' THEN 1 ELSE NULL END) AS closed_count'
                    ),
                    DB::raw(
                        'COUNT(CASE WHEN alerts.status = ' . Status::CANCELLED . ' THEN 1 ELSE NULL END) AS cancelled_count'
                    ),
                    DB::raw('MIN(alerts.created_at) AS min_created_at'),
                    DB::raw("CONVERT_TZ(s.departure_timestamp, p.timezone, 'UTC') AS departure_timestamp_timezone"),
                    DB::raw(
                        "CASE
                        WHEN s.status = 0 THEN 'white'
                        WHEN s.status = -20 THEN 'yellow'
                        WHEN s.status = -60 THEN 'purple'
                        WHEN s.status = -10 THEN 'orange'
                        WHEN s.status = 10 THEN 'blue'
                        WHEN s.status = -30 THEN 'red'
                        WHEN s.status = 20 THEN 'green'
                        WHEN s.status = -40 THEN 'pink'
                        WHEN s.status = 40 THEN 'grey'
                        WHEN s.status = -50 THEN 'black'
                        ELSE 'none'
                    END AS color"
                    )
                ])
                ->join('services as s', 's.id', '=', 'service_id')
                ->join('provinces as p', 'p.id', '=', 's.province_id')
                ->whereDate(DB::raw("CONVERT_TZ(s.departure_timestamp, p.timezone, 'UTC')"), '>=', $twentyFourHoursAgo)
                ->groupBy('service_id', 'p.timezone')
                ->orderByDesc('raw_max_priority')
                ->orderByDesc('departure_timestamp_timezone');

            //TIPOLOGÍA
            foreach (Type::ALL_TYPES as $type) {
                $query->addSelect([
                    DB::raw("IF(SUM(alerts.status = " . Status::CLOSED . " AND alerts.type = $type) > 0, 1, 0) AS closed_" . Type::getTypeName($type)),
                    DB::raw("IF(SUM(alerts.status = " . Status::PENDING . " AND alerts.type = $type) > 0, 1, 0) AS pending_" . Type::getTypeName($type)),
                ]);
            }

            //SI NO ESTÁN VACÍAS $routes, $providers, $sites
            if (!empty($businessUnits) || !empty($sites) || !empty($routes) || !empty($providers)) {
                // Cuando r.primary_site = 1 hacer el join con r.secundary_site, si no, hacer el join con s.primary_site
                $query->join('routes as r', function ($join) {
                    $join->on(DB::raw('ABS(s.external_route_id)'), '=', 'r.id');
                })->join('sites as st', function ($join) {
                    $join->on('st.id', '=', DB::raw('IF(r.primary_site > 0, `r`.`primary_site`, r.secundary_site)'));
                });

                if (!empty($businessUnits)) {
                    $query->where(function ($query) use ($businessUnits) {
                        if (in_array("1", $businessUnits)) {
                            $query->orWhere('st.commuting_site_id', '>', '0');
                        }

                        if (in_array("2", $businessUnits)) {
                            $query->orWhere('st.company_event_id', '>', '0');
                        }
                    });
                }

                if (!empty($sites)) {
                    $query->whereIn('st.id', $sites);
                }
            }

            if (!empty($serviceStatus)) {
                $query->whereIn('s.status', $serviceStatus);
            }

            if (!empty($countries)) {
                $query->whereIn('p.country_id', $countries);
            }

            if (!empty($routes)) {
                $query->whereIn('r.id', $routes);
            }

            if (!empty($providers)) {
                $query->whereIn('r.provider_id', $providers);
            }

            if (!empty($priority)) {
                $query->whereIn('alerts.priority', $priority);
            }

            if (!empty($alertsStatus)) {
                $query->whereIn('alerts.status', $alertsStatus);
            }

            $alerts = $query->paginate($perPage);

            return $alerts->toArray();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            dd($th);
        }
    }

    public function getByServiceAndType(int $service_id, int $type): array
    {
        return $this->getAlertsWhere([
            'service_id' => $service_id,
            'type' => $type
        ]);
    }

    private function getAlertsWhere(array $conditions): array
    {
        try {
            $query = AlertModel::where($conditions)->get();
            $alerts = [];

            foreach ($query as $alertModel) {
                $alerts[] = $alertModel->toArray();
            }

            return $alerts;
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function getServicesWithAlertsDetails(): array
    {
        try {
            $perPage = 10;

            $alertsDetails = AlertModel::select(
                'service_id',
                'alert_count',
                'max_priority',
                'pending_count',
                'working_count',
                'closed_count',
                'cancelled_count',
                'min_created_at'
            )
                ->selectRaw('group_concat(alert_id) as alert_ids')
                ->leftJoin('services', 'alerts.service_id', '=', 'services.id')
                ->leftJoin('routes', 'services.external_route_id', '=', 'routes.external_id')
                ->leftJoin('providers_services', function ($join) {
                    $join->on('providers_services.services_id', '=', 'services.id')
                        ->where('providers_services.status', '=', 1);
                })
                ->groupBy('service_id')
                ->orderByDesc('raw_max_priority')
                ->orderBy('min_created_at')
                ->paginate($perPage);

            return $alertsDetails->toArray();
        } catch (\Throwable $th) {
            dd($th);
        }
    }

}