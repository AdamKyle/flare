<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class QuestsCompleted extends Model {

    protected $table = 'quests_completed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'quest_id',
    ];

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function quest() {
        return $this->belongsTo(Quest::class, 'quest_id', 'id');
    }
}
