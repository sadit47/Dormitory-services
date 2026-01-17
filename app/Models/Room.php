<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $fillable = [
        'code',
        'floor',
        'room_type_id',
        'price_monthly',
        'status', // vacant | occupied | maintenance
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(RoomAssignment::class)
            ->where('status', 'active')
            ->whereNull('end_date')
            ->latestOfMany(); // เอา record ล่าสุด
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
