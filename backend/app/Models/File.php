<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'files';

    protected $fillable = [
        'owner_user_id',
        'ref_type',
        'ref_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'checksum',
    ];

    protected $casts = [
        'size' => 'integer',
        'ref_id' => 'integer',
        'owner_user_id' => 'integer',
    ];
}
