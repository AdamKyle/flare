<?php

namespace App\Admin\Monsters\Transformers;

use App\Flare\Models\Monster;
use League\Fractal\TransformerAbstract;

class MonsterListTransformer extends TransformerAbstract
{
    /**
     * Transform a Monster into its Admin list-row representation.
     *
     * @param  Monster  $monster  Monster to transform.
     * @return array{id: int, name: string, game_map: array{id: int, name: string}|null, max_level: int, xp: int, gold: int, is_celestial_entity: bool, is_raid_monster: bool, is_raid_boss: bool} Admin Monster list-row representation.
     */
    public function transform(Monster $monster): array
    {
        return [
            'id' => $monster->id,
            'name' => $monster->name,
            'game_map' => is_null($monster->gameMap) ? null : ['id' => $monster->gameMap->id, 'name' => $monster->gameMap->name],
            'max_level' => $monster->max_level,
            'xp' => $monster->xp,
            'gold' => $monster->gold,
            'is_celestial_entity' => $monster->is_celestial_entity,
            'is_raid_monster' => $monster->is_raid_monster,
            'is_raid_boss' => $monster->is_raid_boss,
        ];
    }
}
