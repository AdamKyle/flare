<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model {

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
        'type' => 'integer',
        'raid_id' => 'integer',
    ];

    public function raid() {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }
}
