<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoredLogFileState extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_key',
        'file_path',
        'position',
        'file_size',
        'last_scanned_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'file_size' => 'integer',
        'last_scanned_at' => 'datetime',
    ];
}
