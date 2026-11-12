<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitReport extends Model
{
    use HasFactory;

    public const CORRECTABLE_FIELDS = [
        'pic_name',
        'activities',
        'stock_percentage',
        'stock_pcs',
        'notes',
    ];

    protected $hidden = [
        'idempotency_key',
        'idempotency_hash',
    ];

    protected $fillable = [
        'user_id',
        'store_type',
        'store_id',
        'check_in_photo',
        'check_in_lat',
        'check_in_lng',
        'visited_at',
        'idempotency_key',
        'idempotency_hash',
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
        'user_id' => 'integer',
        'store_id' => 'integer',
        'store_visit_id' => 'integer',
        'stock_percentage' => 'integer',
        'stock_pcs' => 'integer',
        'activities' => 'array',
        'photos' => 'array',
        'check_in_lat' => 'float',
        'check_in_lng' => 'float',
        'visited_at' => 'datetime',
        'sales_latitude' => 'float',
        'sales_longitude' => 'float',
        'sales_accuracy' => 'float',
        'distance_from_store' => 'float',
        'is_outside_radius' => 'boolean',
        'location_captured_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(StoreVisit::class, 'store_visit_id');
    }

    public function fieldStore(): BelongsTo
    {
        return $this->belongsTo(FieldStore::class, 'store_id');
    }

    public function regularStore(): BelongsTo
    {
        return $this->belongsTo(StoreModel::class, 'store_id', 'odoo_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(VisitCorrection::class)->orderByDesc('id');
    }

    public function originalData(): array
    {
        return $this->only(self::CORRECTABLE_FIELDS);
    }

    public function effectiveData(): array
    {
        $latestCorrection = $this->relationLoaded('corrections')
            ? $this->corrections->firstWhere('status', 'approved')
            : $this->corrections()
                ->where('status', 'approved')
                ->first();

        return $latestCorrection?->correction_data ?? $this->originalData();
    }
}
