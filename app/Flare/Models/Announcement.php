<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'message',
        'expires_at',
        'event_id',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function event() {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }
}
