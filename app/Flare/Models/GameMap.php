<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameMapFactory;
use App\Flare\Models\Map;
use App\Flare\Models\Traits\WithSearch;

class GameMap extends Model
{

    use WithSearch, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'path',
        'default',
        'kingdom_color',
        'xp_bonus',
        'skill_training_bonus',
        'drop_chance_bonus',
        'enemy_stat_bonus',
    ];

    protected $casts = [
        'default'              => 'boolean',
        'xp_bonus'             => 'float',
        'skill_training_bonus' => 'float',
        'drop_chance_bonus'    => 'float',
        'enemy_stat_bonus'     => 'float',
    ];

    public function maps() {
        return $this->hasMany(Map::class, 'game_map_id', 'id');
    }

    public function mapHasBonuses() {
        $hasBonuses = false;

        if (!is_null($this->xp_bonus) || !is_null($this->skill_training_bonus)
            || !is_null($this->drop_chance_bonus) || !is_null($this->enemy_stat_bonus)
        ) {
            $hasBonuses = true;
        }

        return $hasBonuses;
    }

    protected static function newFactory() {
        return GameMapFactory::new();
    }
}
