<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\NotificationFactory;

class Notification extends Model {

    use HasFactory;

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

    protected static function newFactory() {
        return NotificationFactory::new();
    }
}
