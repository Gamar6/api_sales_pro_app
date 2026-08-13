<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitLog extends Model
{
    use HasFactory;

    protected $table = 'visit_logs';

    protected $fillable = [
        'assignment_id',
        'photo_proof',
        'notes',
    ];

    // Relasi balik ke StoreAssignment
    public function assignment()
    {
        return $this->belongsTo(StoreAssignment::class, 'assignment_id');
    }
}