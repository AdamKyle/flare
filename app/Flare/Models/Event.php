<?php

namespace App\Flare\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'started_at',
        'ends_at',
        'raid_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'type'    => 'integer',
        'raid_id' => 'integer',
        'ends_at' => 'datetime'
    ];

    public function raid() {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function announcement() {
        return $this->hasOne(Announcement::class, 'id', 'event_id');
    }

    protected static function newFactory() {
        return EventFactory::new();
    }
}
