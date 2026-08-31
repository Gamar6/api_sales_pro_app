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
    ];

    protected $casts = [
        'store_visit_id'   => 'integer',
        'stock_percentage' => 'integer',
        'stock_pcs'        => 'integer',
        'activities'       => 'array',
        'photos'           => 'array',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(StoreVisit::class, 'store_visit_id');
    }
}