<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreModel extends Model
{
    use HasFactory;

    protected $table = 'stores';

    protected $fillable = [
        'odoo_id',
        'name',
        'address',
        'city',
        'phone',
        'email',
        'sales_name',
        'latitude',
        'longitude',
        'last_order_date',
        'days_since',
        'weeks_since',
        'retensi_status',
        'aktif_group',
        'avg_retensi_weeks',
        'gap_vs_average',
        'total_sales',
        'priority',
    ];

    protected $casts = [
        'odoo_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'last_order_date' => 'date:Y-m-d',
        'days_since' => 'integer',
        'weeks_since' => 'integer',
        'avg_retensi_weeks' => 'float',
        'gap_vs_average' => 'float',
        'total_sales' => 'float',
        'priority' => 'integer',
    ];

    public function activeClaim()
    {
        return $this->hasOne(StoreAssignment::class, 'store_id', 'odoo_id')
            ->whereIn('status', ['claimed', 'onprogress']);
    }

    public function claims()
    {
        return $this->hasMany(StoreAssignment::class, 'store_id', 'odoo_id');
    }
}
