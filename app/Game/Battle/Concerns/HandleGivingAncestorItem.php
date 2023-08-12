<?php
namespace App\Game\Battle\Concerns;

use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\ItemSkillProgression;
use App\Game\Messages\Events\ServerMessageEvent;

trait HandleGivingAncestorItem {

    public function giveAncientReward(Character $character): Character {
        $item = Item::where('type', 'artifact')->doesntHave('itemSkillProgressions')->inRandomOrder()->first();

        $newItem = $item->duplicate();

        $progressionSkills = $this->createSkillProgressionData($newItem->itemSkill, $newItem);

        ItemSkillProgression::insert($progressionSkills);

        $slot = $character->inventory->slots()->create([
            'item_id'      => $newItem->id,
            'inventory_id' => $character->inventory->id,
        ]);

        event(new ServerMessageEvent($character->user, 'You recieved an Ancient Artifact: ' . $slot->item->name, $slot->id));

        return $character->refresh();
    }

    protected function createSkillProgressionData(ItemSkill $itemSkill, Item $item): array {
        $skillProgressionData = [[
            'current_level'    => 0,
            'item_skill_id'    => $itemSkill->id,
            'current_kill'     => 0,
            'is_training'      => false,
            'item_id'          => $item->id,
        ]];

        if ($itemSkill->children->isNotempty()) {
            foreach ($itemSkill->children as $child) {
                $skillProgressionData = [
                    ...$skillProgressionData,
                    ...$this->createSkillProgressionData($child, $item)
                ];
            }
        }

        return $skillProgressionData;
    }
}