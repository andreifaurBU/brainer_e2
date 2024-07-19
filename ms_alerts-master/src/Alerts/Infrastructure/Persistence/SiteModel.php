<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class SiteModel extends Model
{
    protected $connection = "mysql";
    protected $table = "sites";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;
}