<?php

namespace App\Flare\Models;

use Database\Factories\NpceFactory;
use Database\Factories\NpcFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Traits\WithSearch;

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
        'moves_around_map',
        'must_be_at_same_location',
        'text_command_to_message',
        'x_position',
        'y_position',
    ];

    protected $casts = [
        'moves_around_map'         => 'boolean',
        'must_be_at_same_location' => 'boolean',
        'x_position'               => 'integer',
        'x_position'               => 'integer',
    ];

    public function gameMap() {
        return $this->belongsTo(GameMap::class, 'game_map_id', 'id');
    }

    public function gameMapName(): string {
        return $this->gameMap->name;
    }

    public function commands() {
        return $this->hasMany(NpcCommand::class, 'npc_id', 'id');
    }

    public function quests() {
        return $this->hasMany(Quest::class, 'npc_id', 'id');
    }

    protected static function newFactory() {
        return NpcFactory::new();
    }
}
