<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\NpcFactory;
use App\Flare\Models\Traits\WithSearch;
use App\Flare\Values\NpcTypes;

class Npc extends Model {

    use WithSearch, HasFactory;

    protected $table = 'npcs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'real_name',
        'type',
        'game_map_id',
        'x_position',
        'y_position',
    ];

    protected $casts = [
        'moves_around_map'         => 'boolean',
        'must_be_at_same_location' => 'boolean',
        'x_position'               => 'integer',
        'y_position'               => 'integer',
        'type'                     => 'integer',
    ];

    public function type(): NpcTypes {
        return (new NpcTypes($this->type));
    }

    public function gameMap() {
        return $this->hasOne(GameMap::class, 'id', 'game_map_id');
    }

    public function gameMapName(): string {
        return $this->gameMap->name;
    }

    public function quests() {
        return $this->hasMany(Quest::class, 'npc_id', 'id');
    }

    protected static function newFactory() {
        return NpcFactory::new();
    }
}
