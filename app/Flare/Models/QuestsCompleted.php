<?php

namespace App\Flare\Models;

use Database\Factories\QuestsCompletedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestsCompleted extends Model {

    use HasFactory;

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

    protected static function newFactory() {
        return QuestsCompletedFactory::new();
    }
}
