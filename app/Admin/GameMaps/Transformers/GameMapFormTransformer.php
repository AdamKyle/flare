<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\GameMap;
use Illuminate\Support\Facades\Storage;

class GameMapFormTransformer
{
    /**
     * Transform a Game Map into its Admin save-response representation.
     *
     * @param  GameMap  $gameMap  Game Map to transform.
     * @return array{id: int, name: string, description: string|null, map_url: string, kingdom_color: string, default: bool, can_traverse: bool, only_during_event_type: int|null, xp_bonus: int|float, skill_training_bonus: int|float, drop_chance_bonus: int|float, enemy_stat_bonus: int|float, character_attack_reduction: int|float, required_location_id: int|null} Admin Game Map form-value representation.
     */
    public function transform(GameMap $gameMap): array
    {
        return [
            'id' => $gameMap->id,
            'name' => $gameMap->name,
            'description' => $gameMap->description,
            'map_url' => Storage::disk('maps')->url($gameMap->path),
            'kingdom_color' => $gameMap->kingdom_color,
            'default' => $gameMap->default,
            'can_traverse' => $gameMap->can_traverse,
            'only_during_event_type' => $gameMap->only_during_event_type,
            'xp_bonus' => $gameMap->xp_bonus,
            'skill_training_bonus' => $gameMap->skill_training_bonus,
            'drop_chance_bonus' => $gameMap->drop_chance_bonus,
            'enemy_stat_bonus' => $gameMap->enemy_stat_bonus,
            'character_attack_reduction' => $gameMap->character_attack_reduction,
            'required_location_id' => $gameMap->required_location_id,
        ];
    }
}
