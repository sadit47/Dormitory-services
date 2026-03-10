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

    public function receivedParcels(): HasMany
    {
        return $this->hasMany(Parcel::class, 'received_by_user_id');
    }

    public function handedOverParcels(): HasMany
    {
        return $this->hasMany(Parcel::class, 'picked_up_by_user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function announcementReads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function announcementsCreated(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by_user_id');
    }
}