<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'nohp',
        'role',
        'profile_photo_url',
        'profile_photo_public_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function storeAssignments()
    {
        return $this->hasMany(StoreAssignment::class, 'claimed_by');
    }

    public function storeVisits(): HasMany
    {
        return $this->hasMany(
            StoreVisit::class,
            'sales_id'
        );
    }
}