<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StoreVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'odoo_partner_id',
        'sales_id',
        'visit_date',
        'status',
        'check_in_at',
        'check_out_at',
    ];

    protected $casts = [
        'odoo_partner_id' => 'integer',
        'sales_id'        => 'integer',
        'visit_date'      => 'date:Y-m-d',
        'check_in_at'     => 'datetime',
        'check_out_at'    => 'datetime',
    ];

    /**
     * Relasi ke Sales (User Laravel)
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    /**
     * Relasi 1-to-1 ke Laporan Kunjungan
     */
    public function report(): HasOne
    {
        return $this->hasOne(VisitReport::class, 'store_visit_id');
    }

    /**
     * Scope helper untuk memfilter klaim aktif hari ini
     */
    public function scopeToday($query)
    {
        return $query->whereDate('visit_date', today());
    }
}