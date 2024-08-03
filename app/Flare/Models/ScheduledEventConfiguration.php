<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledEventConfiguration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_type',
        'start_date',
        'last_time_generated',
        'generate_amount',
    ];

    protected $casts = [
        'event_type' => 'integer',
        'start_date' => 'datetime',
        'generate_every' => 'string',
        'generate_amount' => 'integer',
        'last_time_generated' => 'date',
    ];
}
