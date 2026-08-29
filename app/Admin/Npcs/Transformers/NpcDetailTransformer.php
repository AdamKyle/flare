<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\Npc;

class NpcDetailTransformer
{
    /**
     * Transform the supplied internal NPC detail data into its Admin detail representation.
     *
     * @param  array{npc: Npc, quest_count: int, reward_item_count: int}  $detailData  Internal NPC detail data.
     * @return array{id: int, game_map: array{id: int, name: string}, real_name: string, type: int, x_position: int, y_position: int, quest_count: int, reward_item_count: int} Admin NPC detail representation.
     */
    public function transform(array $detailData): array
    {
        /** @var Npc $npc */
        $npc = $detailData['npc'];

        return [
            'id' => $npc->id,
            'game_map' => [
                'id' => $npc->game_map_id,
                'name' => $npc->gameMap?->name ?? '',
            ],
            'real_name' => $npc->real_name,
            'type' => $npc->type,
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
            'quest_count' => $detailData['quest_count'],
            'reward_item_count' => $detailData['reward_item_count'],
        ];
    }
}
