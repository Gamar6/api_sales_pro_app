<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_visit_id',
        'visit_report_id',
        'user_id',
        'previous_data',
        'correction_data',
        'reason',
        'status',
        'approved_at',
        'idempotency_key',
        'idempotency_hash',
    ];

    protected $hidden = [
        'idempotency_key',
        'idempotency_hash',
    ];

    protected $casts = [
        'store_visit_id' => 'integer',
        'visit_report_id' => 'integer',
        'user_id' => 'integer',
        'previous_data' => 'array',
        'correction_data' => 'array',
        'approved_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(StoreVisit::class, 'store_visit_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(VisitReport::class, 'visit_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
