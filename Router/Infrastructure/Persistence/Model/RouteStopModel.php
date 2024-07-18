<?php

namespace Core\Router\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;

class RouteStopModel extends Model
{
    protected $connection = "avantgrupbus";
    protected $table = "stop";
    public $timestamps = false;

}
