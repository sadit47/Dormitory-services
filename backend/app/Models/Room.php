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

    /**
     * assignment ที่ active ล่าสุด
     * ✅ ห้ามใช้ latestOfMany() ถ้าตารางไม่มี created_at
     */
    public function activeAssignment(): HasOne
    {
    return $this->hasOne(RoomAssignment::class)
        ->where('status', 'active')
        ->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhereDate('end_date', '>=', now()->toDateString());
        })
        ->orderByDesc('start_date')   // เอาอันล่าสุด
        ->orderByDesc('id');          // กันชน ถ้า start_date เท่ากัน
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
