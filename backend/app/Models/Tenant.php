<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Tenant extends Model
{
    protected $fillable = [
        'user_id',
        'citizen_id',
        'address',
        'emergency_contact',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    // =========================
    // Relations
    // =========================
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }

    /**
     * ห้องที่กำลังพักอยู่ (ตัว assignment ปัจจุบัน)
     * ใช้ status=active เป็นหลัก + end_date null เป็นตัวกันพลาด
     */
    public function currentAssignment(): HasOne
    {
    return $this->hasOne(RoomAssignment::class)
        ->where('status', 'active')
        ->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhereDate('end_date', '>=', now()->toDateString());
        })
        ->orderByDesc('start_date')
        ->orderByDesc('id');
    }

    public function currentRoom(): HasOneThrough
    {
        return $this->hasOneThrough(
            Room::class,
            RoomAssignment::class,
            'tenant_id', // room_assignments.tenant_id
            'id',        // rooms.id
            'id',        // tenants.id
            'room_id'    // room_assignments.room_id
        )
        ->select('rooms.*')
        ->where('room_assignments.status', 'active')
        ->where(function ($q) {
            $q->whereNull('room_assignments.end_date')
            ->orWhereDate('room_assignments.end_date', '>=', now()->toDateString());
        })
        ->orderByDesc('room_assignments.start_date')
        ->orderByDesc('room_assignments.id');
    }


    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(RepairRequest::class);
    }

    public function cleanings(): HasMany
    {
        return $this->hasMany(CleaningRequest::class);
    }

    public function parcels(): HasMany
    {
    return $this->hasMany(Parcel::class);
    }
}
