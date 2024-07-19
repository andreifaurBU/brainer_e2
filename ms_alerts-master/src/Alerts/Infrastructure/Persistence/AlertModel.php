<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class AlertModel extends Model
{
    protected $table = "alerts";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;

    public function service() {
        return $this->belongsTo(ServiceModel::class, 'service_id');
    }


}