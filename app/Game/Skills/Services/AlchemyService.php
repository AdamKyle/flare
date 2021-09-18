<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Events\UpdateCharacterAlchemyList;
use App\Game\Skills\Services\Traits\SkillCheck;
use App\Game\Skills\Services\Traits\UpdateCharacterGold;
use App\Game\Messages\Events\ServerMessageEvent as GameServerMessageEvent;

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

    public function transmute(Character $character, int $itemId): void {
        $skill = $character->skills->filter(function ($skill) {
            return $skill->type()->isAlchemy();
        })->first();

        $item = Item::find($itemId);

        if (is_null($item)) {
            event(new GameServerMessageEvent($character->user, 'Nope. No item exists.'));

            return;
        }

        if ($item->gold_dust_cost > $character->gold_dust) {
            event(new ServerMessageEvent($character->user, 'not_enough_gold_dust'));

            return;
        }

        if ($item->shards_cost > $character->shards) {
            event(new ServerMessageEvent($character->user, 'not_enough_shards'));

            return;
        }

        $this->attemptTransmute($character, $skill, $item);
    }

    public function attemptTransmute(Character $character, Skill $skill, Item $item): void {
        $this->updateAlchemyCost($character, $item);

        if ($skill->level < $item->skill_level_required) {

            event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));

            $this->pickUpItem($character, $item, $skill, true);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            event(new UpdateCharacterAlchemyList($character->user, $this->fetchAlchemistItems($character)));

            return;
        }

        if ($skill->level >= $item->skill_level_trivial) {

            event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));

            $this->pickUpItem($character, $item, $skill, true);

            event(new UpdateCharacterAlchemyList($character->user, $this->fetchAlchemistItems($character)));

            return;
        }

        $characterRoll = $this->characterRoll($skill);
        $dcCheck       = $this->getDCCheck($skill);

        if ($dcCheck < $characterRoll) {
            $this->pickUpItem($character, $item, $skill);

            event(new UpdateCharacterAlchemyList($character->user, $this->fetchAlchemistItems($character)));

            return;
        }

        event(new ServerMessageEvent($character->user, 'failed_to_transmute'));

        event(new UpdateCharacterAlchemyList($character->user, $this->fetchAlchemistItems($character)));
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
