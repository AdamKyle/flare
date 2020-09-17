<?php

namespace App\Flare\Models;

use Database\Factories\NotificationsDatabaseFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'title',
        'message',
        'status',
        'type',
        'read',
        'url',
    ];

    protected $casts = [
        'read' => 'boolean',
    ];

    public function character() {
        return $this->belongsTo(Charcater::class);
    }
}
