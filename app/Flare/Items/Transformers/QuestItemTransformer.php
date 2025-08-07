<?php

declare(strict_types=1);

namespace App\Flare\Items\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Flare\Models\Location;
use League\Fractal\TransformerAbstract;

class QuestItemTransformer extends TransformerAbstract
{
    public function transform(Item $item): array
    {
        return [
            'id'                  => $item->id,
            'name'                => $item->name,
            'type'                => $item->type,
            'description'         => $item->description,
            'can_drop'            => $item->can_drop,
            'usable'              => $item->usable,
            'craft_only'          => $item->craft_only,
            'move_time_out_mod_bonus' => $item->move_time_out_mod_bonus,
            'fight_time_out_mod_bonus' => $item->fight_time_out_mod_bonus,
            'effect'              => $item->effect,
            'drop_location'       => $this->getDropLocation($item),
            'required_monster'    => $this->getRequiredMonster($item),
            'required_quest'      => $this->getRequiredQuest($item),
            'reward_locations'    => $this->getRewardLocations($item),
            'required_quests'     => $this->getRequiredQuests($item),
            'reward_quests'       => $this->getRewardQuests($item),
            'required_locations'  => $this->getRequiredLocations($item),
        ];
    }

    private function getDropLocation(Item $item): ?array
    {
        if (! $item->relationLoaded('dropLocation')) {
            $item->load('dropLocation.map');
        }

        if (! $item->dropLocation) {
            return null;
        }

        return [
            'id'   => $item->dropLocation->id,
            'name' => $item->dropLocation->name,
            'map'  => $item->dropLocation->map->name,
        ];
    }

    private function getRequiredMonster(Item $item): ?array
    {
        $monster = Monster::where('quest_item_id', $item->id)->with('gameMap')->first();

        if (is_null($monster)) {
            return null;
        }

        return [
            'id'   => $monster->id,
            'name' => $monster->name,
            'map'  => $monster->gameMap->name,
        ];
    }

    private function getRequiredQuest(Item $item): ?array
    {
        $quest = $item->required_quest;

        if (is_null($quest)) {
            return null;
        }

        return [
            'id'   => $quest->id,
            'name' => $quest->name,
            'npc'  => $quest->npc->real_name,
            'map'  => $quest->npc->gameMap->name,
        ];
    }

    private function getRewardLocations(Item $item): array
    {
        return collect($item->locations)
            ->map(fn ($location) => [
                'id'   => $location->id,
                'name' => $location->name,
                'map'  => $location->map->name,
            ])
            ->all();
    }

    private function getRequiredQuests(Item $item): array
    {
        return Quest::where('item_id', $item->id)
            ->orWhere('secondary_required_item', $item->id)
            ->with('npc.gameMap')
            ->get()
            ->map(fn ($quest) => [
                'id'   => $quest->id,
                'name' => $quest->name,
                'npc'  => $quest->npc->real_name,
                'map'  => $quest->npc->gameMap->name,
            ])
            ->toArray();
    }

    private function getRewardQuests(Item $item): array
    {
        return Quest::where('reward_item', $item->id)
            ->with('npc.gameMap')
            ->get()
            ->map(fn ($quest) => [
                'id'   => $quest->id,
                'name' => $quest->name,
                'npc'  => $quest->npc->real_name,
                'map'  => $quest->npc->gameMap->name,
            ])
            ->toArray();
    }

    private function getRequiredLocations(Item $item): array
    {
        return Location::where('required_quest_item_id', $item->id)
            ->with('map')
            ->get()
            ->map(fn ($location) => [
                'id'   => $location->id,
                'name' => $location->name,
                'map'  => $location->map->name,
            ])
            ->toArray();
    }
}
