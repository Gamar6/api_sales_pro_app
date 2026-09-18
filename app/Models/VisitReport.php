<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitReport extends Model
{
    use HasFactory;

    protected $fillable = [
    'store_visit_id',
    'pic_name',
    'activities',
    'stock_percentage',
    'stock_pcs',
    'notes',
    'photos',
    'sales_latitude',
    'sales_longitude',
    'sales_accuracy',
    'distance_from_store',
    'is_outside_radius',
    'location_captured_at',
    ];

    protected $casts = [
        'store_visit_id'       => 'integer',
        'stock_percentage'     => 'integer',
        'stock_pcs'            => 'integer',
        'activities'           => 'array',
        'photos'               => 'array',
        'sales_latitude'       => 'float',
        'sales_longitude'      => 'float',
        'sales_accuracy'       => 'float',
        'distance_from_store'  => 'float',
        'is_outside_radius'    => 'boolean',
        'location_captured_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(StoreVisit::class, 'store_visit_id');
    }
}
