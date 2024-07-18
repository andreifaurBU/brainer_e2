<?php

namespace Core\Router\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;

class RouteConfig extends Model
{
    protected $table = 'brainer_rerouting_route_config';
    protected $fillable = ['route_id', 'activated', 'type', 'time_data', 'created_at', 'modified_at'];
    public $timestamps = false;
}
