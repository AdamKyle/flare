<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledEvent extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_type',
        'raid_id',
        'start_date',
        'end_date',
        'description',
    ];

    protected $casts = [
        'event_type'             => 'integer',
        'raid_id'                => 'integer',
        'start_date'             => 'datetime',
        'end_date'               => 'datetime',
    ];

    public function raid() {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function getTitleOfEvent(): string {
        if (!is_null($this->raid)) {
            return $this->raid->name;
        }

        return 'Event Name';
    }
}
