<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'owner_name',
        'phone_number',
        'address',
        'latitude',
        'longitude',
        'photo_url',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitReports(): HasMany
    {
        return $this->hasMany(VisitReport::class, 'store_id')
            ->where('store_type', 'field');
    }

    public function storeVisits(): HasMany
    {
        return $this->hasMany(StoreVisit::class);
    }
}
