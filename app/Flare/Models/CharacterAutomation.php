<?php

namespace App\Flare\Models;

use App\Flare\Values\AutomationType;
use Database\Factories\CelestialFightFactory;
use Database\Factories\CharacterAutomationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterAutomation extends Model
{

    use HasFactory;

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
        'completed_at',
        'move_down_monster_list_every',
        'previous_level',
        'current_level',
        'attack_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'type'                         => 'integer',
        'started_at'                   => 'datetime',
        'completed_at'                 => 'datetime',
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

    protected static function newFactory() {
        return CharacterAutomationFactory::new();
    }
}
