<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairRequest extends Model
{
    protected $table = 'repair_requests';

    protected $fillable = [
        'tenant_id',
        'room_id',
        'title',
        'description',
        'priority',
        'status',
        'requested_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'tenant_id'     => 'integer',
        'room_id'       => 'integer',
        'created_by'    => 'integer',
        'requested_at'  => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function files(): HasMany
    {
        // รูปแจ้งซ่อมเก็บ ref_type = 'repair_image'
        return $this->hasMany(File::class, 'ref_id')->where('ref_type', 'repair_image');
    }
}
