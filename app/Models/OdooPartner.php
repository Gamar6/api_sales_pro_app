<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooPartner extends Model
{
    protected $connection = 'odoo';
    
    protected $table = 'res_partner';
    
    protected $primaryKey = 'id';
    public $timestamps = false;
}