<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id','payer_user_id','amount','method','paid_at','status','verified_by','verified_at','note'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'ref_id')->where('ref_type', 'payment');
    }
}
