<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Character;

class Notification extends Model
{

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
    ];

    public function character() {
        return $this->belongsTo(Charcater::class);
    }
}
