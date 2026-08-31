<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\StoreModel;

class StoreAssignment extends Model
{
    public function store()
    {
        return $this->belongsTo(StoreModel::class, 'store_id', 'odoo_id');
    }
}
