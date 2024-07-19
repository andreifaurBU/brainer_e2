<?php

namespace Core\Alerts\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class ProviderModel extends Model
{
    protected $connection = "busup_providers";
    protected $table = "providers";
    protected $guarded = [];
    public $timestamps = false;
    public $updated = false;
}