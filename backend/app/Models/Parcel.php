<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcel extends Model
{
    protected $table = 'parcels';

    protected $fillable = [
        'tenant_id',
        'room_id',
        'tracking_no',
        'courier',
        'sender_name',
        'note',
        'status',
        'received_at',
        'received_by_user_id',
        'picked_up_at',
        'picked_up_by_user_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'room_id' => 'integer',
        'received_by_user_id' => 'integer',
        'picked_up_by_user_id' => 'integer',
        'received_at' => 'datetime',
        'picked_up_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function pickedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_by_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'ref_id', 'id')
            ->where('ref_type', 'parcel_image');
    }
}