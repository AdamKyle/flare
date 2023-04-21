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
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];
}
