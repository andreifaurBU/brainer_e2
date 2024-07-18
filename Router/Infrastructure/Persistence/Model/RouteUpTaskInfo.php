<?php

namespace Core\Router\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;

class RouteUpTaskInfo extends Model
{
    protected $table = 'brainer_routeup_task_info';
    protected $fillable = ['file_id', 'external_task_id', 'user', 'constraints', 'created_at'];
    public $timestamps = false;
}
