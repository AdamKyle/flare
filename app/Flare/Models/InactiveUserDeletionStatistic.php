<?php

namespace App\Flare\Models;

use Database\Factories\InactiveUserDeletionStatisticFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InactiveUserDeletionStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'deleted_count',
        'tracked_at',
    ];

    protected $casts = [
        'deleted_count' => 'integer',
        'tracked_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return InactiveUserDeletionStatisticFactory::new();
    }
}
