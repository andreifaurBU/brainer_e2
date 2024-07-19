<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class ProviderServiceModel extends Model
{
    protected $connection = "busup_providers";
    protected $table = "providers_services";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;
}