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

class AlchemyService {
    use ResponseBuilder, SkillCheck, UpdateCharacterGold;

    public function fetchAlchemistItems($character) {
        $skill = $character->skills->filter(function ($skill) {
            return $skill->type()->isAlchemy();
        })->first();

        return Item::where('can_craft', true)
            ->where('crafting_type', 'alchemy')
            ->where('skill_level_required', '<=', $skill->level)
            ->where('item_prefix_id', null)
            ->where('item_suffix_id', null)
            ->orderBy('gold_dust_cost', 'asc')
            ->select('id', 'name', 'gold_dust_cost', 'shards_cost')
            ->get();
    }

    public function transmute(Character $character, int $itemId): array {
        $skill = $character->skills->filter(function ($skill) {
            return $skill->type()->isAlchemy();
        })->first();

        $item = Item::find($itemId);

        if (is_null($item)) {
            return $this->errorResult('Item does not exist.');
        }

        if ($item->gold_dust_cost > $character->gold_dust) {
            event(new ServerMessageEvent($character->user, 'not_enough_gold_dust'));

            return $this->errorResult('not enough gold dust.');
        }

        if ($item->shards_cost > $character->shards) {
            event(new ServerMessageEvent($character->user, 'not_enough_shards'));

            return $this->errorResult('not enough shards.');
        }

        return $this->attemptTransmute($character, $skill, $item);
    }

    public function attemptTransmute(Character $character, Skill $skill, Item $item): array {
        $this->updateAlchemyCost($character, $item);

        if ($skill->level < $item->skill_level_required) {

            event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));
            event(new CraftedItemTimeOutEvent($character->refresh()));

            return $this->successResult([
                'items' => $this->fetchAlchemistItems($character),
            ]);
        }

        if ($skill->level >= $item->skill_level_trivial) {

            event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));
            event(new CraftedItemTimeOutEvent($character->refresh()));

            $this->pickUpItem($character, $item, $skill, true);

            return $this->successResult([
                'items' => $this->fetchAlchemistItems($character),
            ]);
        }

        $characterRoll = $this->characterRoll($skill);
        $dcCheck       = $this->getDCCheck($skill);

        if ($dcCheck < $characterRoll) {
            $this->pickUpItem($character, $item, $skill);

            event(new CraftedItemTimeOutEvent($character->refresh()));

            return $this->successResult([
                'items' => $this->fetchAlchemistItems($character),
            ]);
        }

        event(new ServerMessageEvent($character->user, 'failed_to_transmute'));

        event(new CraftedItemTimeOutEvent($character->refresh()));

        return $this->successResult([
            'items' => $this->fetchAlchemistItems($character),
        ]);
    }

    private function pickUpItem(Character $character, Item $item, Skill $skill, bool $tooEasy = false) {
        if ($this->attemptToPickUpItem($character, $item)) {

            if (!$tooEasy) {
                event(new UpdateSkillEvent($skill));
            }
        }
    }

    private function attemptToPickUpItem(Character $character, Item $item): bool {
        if (!$character->isInventoryFull()) {

            $character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            event(new ServerMessageEvent($character->user, 'transmuted', $item->name));

            return true;
        }

        event(new ServerMessageEvent($character->user, 'inventory_full'));

        return false;
    }
}
