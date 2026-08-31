<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreAssignment extends Model
{
    use HasFactory;

    protected $table = 'store_assignments';

    protected $fillable = [
        'store_id',
        'claimed_by',
        'status',
        'claimed_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(StoreModel::class, 'store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function visitLog()
    {
        return $this->hasOne(VisitLog::class, 'assignment_id');
    }
}