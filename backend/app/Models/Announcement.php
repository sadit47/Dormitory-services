<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'type',
        'status',
        'is_pinned',
        'starts_at',
        'ends_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'created_by_user_id' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'ref_id', 'id')
            ->where('ref_type', 'announcement_image');
    }

    public function isActiveNow(): bool
    {
        $now = now();

        if ($this->status !== 'published') {
            return false;
        }

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }
}