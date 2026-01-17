<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $fillable = [
        'name','email','password','role','phone','status',
        'admin_password_enc',
    ];

    protected $hidden = [
        'password','remember_token',
    ];

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payer_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'owner_user_id');
    }
}
