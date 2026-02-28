<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = [
        'name','email','password','role','phone','status',
        'admin_password_enc',
    ];

    protected $hidden = [
        'password','remember_token','admin_password_enc',
    ];

    protected $casts = [
        'password' => 'hashed',
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
