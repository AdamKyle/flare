<?php

namespace App\Admin\Import\GuideQuests\Sheets;

use App\Flare\Models\Item;
use App\Flare\Models\Quest;
use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\PassiveSkill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class GuideQuestsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $guideQuest = array_combine($rows[0]->toArray(), $row->toArray());

                $guideQuestData = $this->returnCleanAffix($guideQuest);

                if (is_null($guideQuestData)) {
                    continue;
                } else {
                    $foundGuideQuest = GuideQuest::where('name', $guideQuestData['name']);

                    if (!is_null($foundGuideQuest)) {
                        $foundGuideQuest->update($guideQuestData);
                    } else {
                        GuideQuest::create($guideQuestData);
                    }
                }
            }
        }
    }

    protected function returnCleanAffix(array $data) {

        $gameMap        = GameMap::where('name', $data['required_game_map_id'])->first();
        $skill          = GameSkill::where('name', $data['required_skill'])->first();
        $secondarySkill = GameSkill::where('name', $data['required_secondary_skill'])->first();
        $passiveSkill   = PassiveSkill::where('name', $data['required_passive_skill'])->first();
        $faction        = Faction::whereHas('gameMap', function ($query) use ($data) {
            $query->where('name', $data['required_faction_id']);
        })->first();
        $requiredItem   = Item::where('name', $data['required_quest_item_id'])->where('type', 'quest')->first();
        $secondaryItem  = Item::where('name', $data['secondary_quest_item_id'])->where('type', 'quest')->first();
        $quest          = Quest::where('name', $data['required_quest_id'])->first();

        if (is_null($skill)) {
            $data['required_skill_level'] = null;
            $data['required_skill']       = null;
        } else {
            $data['required_skill'] = $skill->id;
        }

        if (is_null($secondarySkill)) {
            $data['required_secondary_skill_level'] = null;
            $data['required_secondary_skill']       = null;
        } else {
            $data['required_secondary_skill'] = $secondarySkill->id;
        }

        if (is_null($passiveSkill)) {
            $data['required_passive_level'] = null;
            $data['required_passive_skill'] = null;
        } else {
            $data['required_passive_skill'] = $passiveSkill->id;
        }

        if (is_null($faction)) {
            $data['required_faction_id']    = null;
            $data['required_faction_level'] = null;
        } else {
            $data['required_faction_id'] = $faction->id;
        }

        if (is_null($requiredItem)) {
            $data['required_quest_item_id'] = null;
        } else {
            $data['required_quest_item_id'] = $requiredItem->id;
        }

        if (is_null($secondaryItem)) {
            $data['secondary_quest_item_id'] = null;
        } else {
            $data['secondary_quest_item_id'] = $secondaryItem->id;
        }

        if (is_null($quest)) {
            $data['required_quest_id'] = null;
        } else {
            $data['required_quest_id'] = $quest->id;
        }

        if (is_null($gameMap)) {
            $data['required_game_map_id'] = null;
        } else {
            $data['required_game_map_id'] = $gameMap->id;
        }

        return $data;
    }
}
