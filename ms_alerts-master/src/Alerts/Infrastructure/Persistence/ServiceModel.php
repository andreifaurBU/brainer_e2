<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class ServiceModel extends Model
{
    protected $connection = "mysql";
    protected $table = "services";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;

    protected $casts = [
        'external_route_id' => 'integer',
        'external_id' => 'integer',
        'external_budget_id' => 'integer',
    ];

    public static $colorStatusMapping = [
        'white' => 0,
        'yellow' => -20,
        'purple' => -60,
        'orange' => -10,
        'blue' => 10,
        'red' => -30,
        'green' => 20,
        'pink' => -40,
        'grey' => 40,
        'black' => -50
    ];

    public function getExternalRouteIdAttribute($value)
    {
        return abs($value);
    }

    public function getExternalIdAttribute($value)
    {
        return abs($value);
    }

    public function getExternalBudgetIdAttribute($value)
    {
        return abs($value);
    }


    public function route() {
        return $this->belongsTo(RouteModel::class, 'external_route_id');
    }

    public function site()
    {
        return $this->belongsTo(SiteModel::class, 'external_route_id', 'site_id');
    }
}