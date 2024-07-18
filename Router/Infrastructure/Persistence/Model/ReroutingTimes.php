<?php

namespace Core\Router\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;

class ReroutingTimes extends Model
{
    protected $table = 'brainer_rerouting_times';
    protected $fillable = ['route_id', 'service_id', 'expected_time', 'executed_time', 'status', 'not_rerouting_reason', 'raw_response', 'processed_response'];
    public $timestamps = false;
}
