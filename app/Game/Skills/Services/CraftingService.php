<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\Traits\SkillCheck;
use App\Game\Skills\Services\Traits\UpdateCharacterGold;

class CraftingService {

    use ResponseBuilder, SkillCheck, UpdateCharacterGold;

    /**
     * Fetch all craftable items for a character.
     *
     * The params variable is the request params.
     *
     * @param Character $character
     * @param array $params
     * @return Collection
     */
    public function fetchCraftableItems(Character $character, array $params): Collection {
        $skill = $this->fetchCraftingSkill($character, $params['crafting_type']);

        return $this->getItems($params['crafting_type'], $skill);
    }

    /**
     * Attempts to craft the item.
     *
     * The params are the request params.
     *
     * Gold is only taken from a player if they can pick up the item they crafted or
     * if they fail to craft them item.
     *
     * @param Character $character
     * @param array params
     * @return array
     */
    public function craft(Character $character, array $params): array {
        $item = Item::find($params['item_to_craft']);
        $skill = $this->fetchCraftingSkill($character, $params['type']);

        if (is_null($item)) {
            return $this->errorResult('Invalid item.');
        }

        if ($item->cost > $character->gold) {
            event(new ServerMessageEvent($character->user, 'not_enough_gold'));

            return $this->successResult([
                'items' => $this->getItems($params['type'], $skill)
            ]);
        }

        $this->attemptToCraftItem($character, $skill, $item);

        event(new CraftedItemTimeOutEvent($character->refresh()));

        return $this->successResult([
            'items' => $this->getItems($params['type'], $skill)
        ]);
    }

    protected function attemptToCraftItem(Character $character, Skill $skill, Item $item) {
        if ($skill->level < $item->skill_level_required) {
            event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));

            return;
        }

        if ($skill->level >= $item->skill_level_trivial) {
            event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));

            $this->pickUpItem($character, $item, $skill, true);

            return;
        }

        $characterRoll = $this->characterRoll($skill);
        $dcCheck       = $this->getDCCheck($skill);

        if ($dcCheck < $characterRoll) {
            $this->pickUpItem($character, $item, $skill);

            return;
        }

        event(new ServerMessageEvent($character->user, 'failed_to_craft'));

        $this->updateCharacterGold($character, $item->cost, $skill);

        return;
    }

    protected function fetchCraftingSkill(Character $character, string $craftingType): Skill {
        return $character->skills->where('name', ucfirst($craftingType) . ' Crafting')->first();
    }

    protected function getItems($craftingType, Skill $skill): Collection {
        return Item::where('can_craft', true)
                    ->where('crafting_type', strtolower($craftingType))
                    ->where('skill_level_required', '<=', $skill->level)
                    ->where('item_prefix_id', null)
                    ->where('item_suffix_id', null)
                    ->orderBy('cost', 'asc')
                    ->get();
    }

    private function pickUpItem(Character $character, Item $item, Skill $skill, bool $tooEasy = false) {
        if ($this->attemptToPickUpItem($character, $item)) {

            if (!$tooEasy) {
                event(new UpdateSkillEvent($skill));
                $this->updateCharacterGold($character, $item->cost);
            }
        }
    }

    private function attemptToPickUpItem(Character $character, Item $item): bool {
        if ($character->inventory->slots->count() !== $character->inventory_max) {

            $character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            event(new ServerMessageEvent($character->user, 'crafted', $item->name));

            return true;
        }

        event(new ServerMessageEvent($character->user, 'inventory_full'));

        return false;
    }

}
