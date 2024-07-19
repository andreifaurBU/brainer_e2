<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    protected $connection = "busup_providers";
    protected $table = "vehicles";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;
}