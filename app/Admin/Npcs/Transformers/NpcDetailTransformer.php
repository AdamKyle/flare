<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\Npc;

class NpcDetailTransformer
{
    /**
     * Transform the supplied internal NPC detail data into its Admin detail representation.
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
