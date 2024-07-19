<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class ProvinceModel extends Model
{
    protected $connection = "mysql";
    protected $table = "provinces";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;


}