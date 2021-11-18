<?php

namespace App\Flare\Models;

use App\Game\Automation\Values\AutomationType;
use Illuminate\Database\Eloquent\Model;

class CharacterAutomation extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'monster_id',
        'type',
        'started_at',
        'move_down_monster_list_every',
        'previous_level',
        'current_level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'type'                         => 'integer',
        'started_at'                   => 'datetime',
        'move_down_monster_list_every' => 'integer',
        'previous_level'               => 'integer',
        'current_level'                => 'integer',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function monster() {
        return $this->belongsTo(Monster::class);
    }

    public function type(): AutomationType {
        return (new AutomationType($this->type));
    }
}
