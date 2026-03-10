<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementTarget extends Model
{
    protected $table = 'announcement_targets';

    protected $fillable = [
        'announcement_id',
        'target_type',
        'target_id',
    ];

    protected $casts = [
        'announcement_id' => 'integer',
        'target_id' => 'integer',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}