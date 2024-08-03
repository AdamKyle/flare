<?php

namespace App\Flare\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

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

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    protected static function newFactory()
    {
        return AnnouncementFactory::new();
    }
}
