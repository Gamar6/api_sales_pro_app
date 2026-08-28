<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'odoo_id', 'name', 'address', 'city', 'phone', 'email', 'sales_name',
        'latitude', 'longitude', 'last_order_date', 'days_since', 'weeks_since',
        'retensi_status', 'aktif_group', 'avg_retensi_weeks', 'gap_vs_average',
        'total_sales', 'priority'
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