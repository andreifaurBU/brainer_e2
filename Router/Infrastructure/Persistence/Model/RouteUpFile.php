<?php

namespace Core\Router\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;

class RouteUpFile extends Model
{
    protected $table = 'brainer_routeup_files';
    protected $fillable = ['file_name', 'file_type', 'file_size', 'file_content', 'upload_date'];
    public $timestamps = false;
}
