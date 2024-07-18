<?php

namespace Core\Router\Infrastructure\Persistence;

use Core\Router\Domain\Repositories\RouteUpReportsRepository;
use Core\Router\Infrastructure\Persistence\Libraries\BigQueryLibrary;

class BigQueryRouteUpReports implements RouteUpReportsRepository
{

    private string $projectId = "micro-rigging-390414";
    private string $dataset = "avantgrupbus";

    private BigQueryLibrary $bigQueryLibrary;

    public function __construct()
    {
        $this->bigQueryLibrary = new BigQueryLibrary('stops', $this->dataset, 1, $this->projectId);
    }

    public function getReportGoing(
        array $route_codes,
        string $site,
        string $arrival_time_from,
        string $arrival_time_to,
        string $date_from,
        string $date_to
    ){
        $route_codes = array_map(function($item) { return '"' . $item . '"'; }, $route_codes);
        $sql = "
            WITH
            #aqui obtenemos los tickets asociados a los parámetros de entrada
            tickets
            as (select st.ticket_id,st.service_id, st.route_stop_id, st.route_stop_destination_id
            from busup.services_tickets st
            INNER JOIN busup.services s on st.service_id = s.id
            inner join busup.routes r on s.external_route_id = r.external_id
            inner join busup.commuting_sites_routes csr on r.id  = csr.route_id
            inner join busup.commuting_sites cs on cs.id = csr.site_id
            where s.status IN (40,-50)
            and (s.timestamp >=  '" . $date_from . "' and s.timestamp <= '" . $date_to . "' )
            #especificamos las rutas que queremos
            and r.invitation_code IN (" . implode(',', $route_codes) . ")),

            #obtenemos informacion de las rutas y las paradas asociadas
            routes
            as (
            select r.id, TIME(s.arrival_timestamp) as time_arrival, JSON_EXTRACT(cs.shape, '$.latitude') as lat,JSON_EXTRACT(cs.shape, '$.longitude') as lng,count(s.id) as total
            from  busup.services s
            inner join busup.routes r on s.external_route_id = r.external_id
            inner join busup.commuting_sites_routes csr on r.id  = csr.route_id
            inner join busup.commuting_sites cs on cs.id = csr.site_id
            where cs.name = '" . $site . "'
            and s.status IN (40,-50)
            and (s.timestamp >=  '" . $date_from . "' and s.timestamp <= '" . $date_to . "' )
            and r.invitation_code IN (" . implode(',', $route_codes) . ")
            group by r.id, time_arrival,lat, lng),

            tot as (
            select ss.id,r.pax,ss.lat_wgs84 as lat_stop, ss.lng_wgs84 as lng_stop, r.invitation_code,rr.lat, rr.lng,rr.time_arrival,rr.total as total_services, count(*) as total_tickets,STRING_AGG(CAST(up.user_id as STRING), ',') as user
            from busup.routes r
            inner join routes rr on r.id = rr.id
            inner join busup.route_stops rs on rs.route_id = r.id
            inner join avantgrupbus.stop ss on CAST(ss.id as STRING) = rs.external_stop_id
            inner join tickets t on t.route_stop_id = rs.id
            inner join busup.tickets tt on tt.id = t.ticket_id
            inner join busup.user_plans up on up.id = tt.user_plan_id
            #espeficiamos el 'turno'
            WHERE rr.time_arrival BETWEEN '" . $arrival_time_from . ":00' AND '" . $arrival_time_to . ":00'
            group by ss.id, ss.lat_wgs84,r.pax, ss.lng_wgs84,r.invitation_code, rr.lat, rr.lng,rr.time_arrival, rr.total),

            temp as(
            select id,user as users_id,lat_stop,lng_stop,SUBSTRING(lat, 2, LENGTH(lat) - 2) as site_lat, SUBSTRING(lng, 2, LENGTH(lng) - 2) as site_lng, CEIL(SUM(total_tickets) / NULLIF(SUM(total_services), 0)) AS rounded_average
            from tot
            group by id,user,lat_stop, lng_stop,lat,lng)

            select id, STRING_AGG(users_id,', ') AS users_agg,lat_stop,lng_stop,site_lat, site_lng,SUM(rounded_average)
            from temp
            group by id,lat_stop,lng_stop,site_lat, site_lng
            order by id asc
        ";

        return $this->bigQueryLibrary->select($sql, null);
    }

    public function getReportReturn(
        array $route_codes,
        string $site,
        string $arrival_time_from,
        string $arrival_time_to,
        string $date_from,
        string $date_to
    ){
        $route_codes = array_map(function($item) { return '"' . $item . '"'; }, $route_codes);
        $sql = "
            WITH
            tickets
            as (select st.ticket_id, st.service_id, st.route_stop_id, st.route_stop_destination_id
            from busup.services_tickets st
            INNER JOIN busup.services s on st.service_id = s.id
            inner join busup.routes r on s.external_route_id = r.external_id
            inner join busup.commuting_sites_routes csr on r.id  = csr.route_id
            inner join busup.commuting_sites cs on cs.id = csr.site_id
            where s.status IN (40,-50)
            and (s.timestamp >=  '" . $date_from . "' and s.timestamp <= '" . $date_to . "' )
            and r.invitation_code IN (" . implode(',', $route_codes) . ")),

            routes
            as (
            select r.id, TIME(s.departure_timestamp) as time_departure, JSON_EXTRACT(cs.shape, '$.latitude') as lat,JSON_EXTRACT(cs.shape, '$.longitude') as lng,count(s.id) as total
            from  busup.services s
            inner join busup.routes r on s.external_route_id = r.external_id
            inner join busup.commuting_sites_routes csr on r.id  = csr.route_id
            inner join busup.commuting_sites cs on cs.id = csr.site_id
            where cs.name = '" . $site . "'
            and s.status IN (40,-50)
            and (s.timestamp >=  '" . $date_from . "' and s.timestamp <= '" . $date_to . "' )
            and r.invitation_code IN (" . implode(',', $route_codes) . ")
            group by r.id, time_departure,lat, lng)
            ,
            tot as (select ss.id, r.pax,ss.lat_wgs84 as lat_stop, ss.lng_wgs84 as lng_stop, r.invitation_code,rr.lat, rr.lng,rr.total as total_services, count(*) as total_tickets,STRING_AGG(CAST(up.user_id as STRING), ',') as user
            from busup.routes r
            inner join routes rr on r.id = rr.id
            inner join busup.route_stops rs on rs.route_id = r.id
            inner join avantgrupbus.stop ss on CAST(ss.id as STRING) = rs.external_stop_id
            inner join tickets t on t.route_stop_destination_id = rs.id
            inner join busup.tickets tt on tt.id = t.ticket_id
            inner join busup.user_plans up on up.id = tt.user_plan_id
            WHERE rr.time_departure BETWEEN '" . $arrival_time_from . ":00' AND '" . $arrival_time_to . ":00'
            group by ss.id, ss.lat_wgs84,r.pax, ss.lng_wgs84,r.invitation_code, rr.lat, rr.lng, rr.total
            order by r.invitation_code asc),
            temp as(
            select id,user as users_id,lat_stop,lng_stop,SUBSTRING(lat, 2, LENGTH(lat) - 2) as site_lat, SUBSTRING(lng, 2, LENGTH(lng) - 2) as site_lng, CEIL(SUM(total_tickets) / NULLIF(SUM(total_services), 0)) AS rounded_average
            from tot
            group by id,user,lat_stop, lng_stop,lat,lng)

            select id, STRING_AGG(users_id,', ') AS users_agg,lat_stop,lng_stop,site_lat, site_lng,SUM(rounded_average)
            from temp
            group by id,lat_stop,lng_stop,site_lat, site_lng
            order by id asc
            ";

        return $this->bigQueryLibrary->select($sql);
    }

}
