<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class DriverModel extends Model
{
    protected $connection = "busup_providers";
    protected $table = "drivers";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;
}