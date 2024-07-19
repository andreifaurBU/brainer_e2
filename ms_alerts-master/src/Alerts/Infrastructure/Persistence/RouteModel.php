<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class RouteModel extends Model
{
    protected $connection = "mysql";
    protected $table = "routes";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;

    protected $casts = [
        'external_id' => 'integer',
    ];

    public function getExternalIdAttribute($value)
    {
        return abs($value);
    }

    public function service()
    {
        return $this->hasMany(ServiceModel::class, 'external_route_id');
    }

    public function site()
    {
        return $this->belongsTo(SiteModel::class, 'primary_site');
    }

    public function province()
    {
        return $this->belongsTo(ProvinceModel::class, 'province_id');
    }


}