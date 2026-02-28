<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'payer_user_id',
        'amount',
        'method',
        'paid_at',
        'status',
        'verified_by',
        'verified_at',
        'note',
    ];

    protected $casts = [
        'invoice_id' => 'integer',
        'payer_user_id' => 'integer',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_by' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    // ✅ ไฟล์สลิป (ตาราง files ใช้ ref_type/ref_id)
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'ref_id', 'id')
            ->where('ref_type', 'payment');
    }
}
