<?php

namespace App\Admin\Import\Quests\Sheets;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $quest = array_combine($rows[0]->toArray(), $row->toArray());

                $questData = $this->returnCleanItem($quest);

                if (!empty($questData)) {
                    Quest::updateOrCreate(['name' => $questData['name']], $questData);
                }
            }
        }
    }

    protected function returnCleanItem(array $quest) {
        $npc = Npc::where('real_name', $quest['npc_id'])->first();

        if (is_null($npc)) {
            return [];
        }

        $quest['npc_id'] = $npc->id;

        if (isset($quest['item_id'])) {
            $requiredItem = Item::where('name', $quest['item_id'])->first();

            if (is_null($requiredItem)) {
                $quest['item_id'] = null;
            } else {
                $quest['item_id'] = $requiredItem->id;
            }
        } else {
            $quest['item_id'] = null;
        }

        if (isset($quest['secondary_required_item'])) {
            $requiredItem = Item::where('name', $quest['secondary_required_item'])->first();

            if (is_null($requiredItem)) {
                $quest['secondary_required_item'] = null;
            } else {
                $quest['secondary_required_item'] = $requiredItem->id;
            }
        } else {
            $quest['secondary_required_item'] = null;
        }


        if (isset($quest['reward_item'])) {
            $item = Item::where('name', $quest['reward_item'])->first();

            if (is_null($item)) {
                $quest['reward_item'] = null;
            }

            $quest['reward_item'] = $item->id;
        } else {
            $quest['reward_item'] = null;
        }

        if (!isset($quest['unlocks_skill'])) {
            $quest['unlocks_skill'] = false;
        } else {
            $skill = GameSkill::where('name', $quest['unlocks_skill'])->first();

            if (is_null($skill)) {
                $quest['unlocks_skill'] = false;
            } else {
                $quest['unlocks_skill'] = $skill->id;
            }
        }

        if (!isset($quest['parent_quest_id'])) {
            $quest['parent_quest_id'] = null;
        } else {
            $foundQuest = Quest::where('name', $quest['parent_quest_id'])->first();

            if (is_null($foundQuest)) {
                $quest['parent_quest_id'] = null;
            } else {
                $quest['parent_quest_id'] = $foundQuest->id;
            }
        }

        if (!isset($quest['faction_game_map_id'])) {
            $quest['faction_game_map_id'] = null;
        } else {
            $map = GameMap::where('name', $quest['faction_game_map_id'])->first();

            if (is_null($quest)) {
                $quest['faction_game_map_id'] = null;
            } else {
                $quest['faction_game_map_id'] = $map->id;
            }
        }

        if (!isset($quest['required_faction_level'])) {
            $quest['required_faction_level'] = null;
        }

        return $quest;
    }
}
