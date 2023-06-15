<?php

namespace App\Admin\Import\Quests\Sheets;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $quest = array_combine($rows[0]->toArray(), $row->toArray());

                $questData = $this->returnCleanItem($quest);

                if (!empty($questData)) {
                    Quest::updateOrCreate(['id' => $questData['id']], $questData);
                }
            }
        }
    }

    protected function returnCleanItem(array $quest) {
        $npc = Npc::find($quest['npc_id']);

        if (is_null($npc)) {
            return [];
        }

        $quest['npc_id'] = $npc->id;

        if (isset($quest['item_name'])) {
            $requiredItem = Item::where('name', $quest['item_name'])->first();

            if (is_null($requiredItem)) {
                $quest['item_id'] = null;
            } else {
                $quest['item_id'] = $requiredItem->id;
            }
        } else {
            $quest['item_id'] = null;
        }

        if (isset($quest['secondary_required_item_name'])) {
            $requiredItem = Item::where('name', $quest['secondary_required_item_name'])->first();

            if (is_null($requiredItem)) {
                $quest['secondary_required_item'] = null;
            } else {
                $quest['secondary_required_item'] = $requiredItem->id;
            }
        } else {
            $quest['secondary_required_item'] = null;
        }

        if (isset($quest['reward_item_name'])) {
            $item = Item::where('name', $quest['reward_item_name'])->first();

            if (is_null($item)) {
                $quest['reward_item'] = null;
            } else {
                $quest['reward_item'] = $item->id;
            }
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
            $foundQuest = Quest::find($quest['parent_quest_id']);

            if (is_null($foundQuest)) {
                $quest['parent_quest_id'] = null;
            } else {
                $quest['parent_quest_id'] = $foundQuest->id;
            }
        }

        if (!isset($quest['faction_game_map_id'])) {
            $quest['faction_game_map_id'] = null;
        } else {
            $map = GameMap::find($quest['faction_game_map_id']);

            if (is_null($map)) {
                $quest['faction_game_map_id'] = null;
            }
        }

        if (!isset($quest['required_faction_level'])) {
            $quest['required_faction_level'] = null;
        }

        if (!isset($quest['is_parent'])) {
            $quest['is_parent'] = false;
        }

        if (!isset($quest['required_passive_skill'])) {
            $quest['required_passive_level'] = null;
        } else {
            $passive = PassiveSkill::find($quest['required_passive_skill']);

            if (is_null($passive)) {
                $quest['required_passive_level'] = null;
            }
        }

        if (!isset($quest['raid_id'])) {
            $quest['raid_id'] = null;
        } else {
            $raid = Raid::find($quest['raid_id']);

            if (is_null($raid)) {
                $quest['raid_id'] = null;
            }
        }

        if (!isset($quest['required_quest_id'])) {
            $quest['required_quest_id'] = null;
        } else {
            $raid = Quest::find($quest['required_quest_id']);

            if (is_null($raid)) {
                $quest['required_quest_id'] = null;
            }
        }

        unset($quest['item_name']);
        unset($quest['secondary_required_item_name']);
        unset($quest['reward_item_name']);

        return $quest;
    }
}
