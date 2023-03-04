<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\Traits\SkillCheck;
use App\Game\Skills\Services\Traits\UpdateCharacterGold;
use App\Game\Messages\Events\ServerMessageEvent as GameServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;

class AlchemyService {
    use ResponseBuilder, SkillCheck, UpdateCharacterGold;

    public function fetchAlchemistItems(Character $character, bool $showMerchantMessage = true) {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY)->first();
        $skill     = Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();



        $items = Item::where('can_craft', true)
                     ->where('crafting_type', 'alchemy')
                     ->where('skill_level_required', '<=', $skill->level)
                     ->where('item_prefix_id', null)
                     ->where('item_suffix_id', null)
                     ->orderBy('skill_level_required', 'asc')
                     ->select('id', 'name', 'gold_dust_cost', 'shards_cost')
                     ->get();

        if ($character->classType()->isMerchant()) {

            $items = $items->transform(function($item) {
                $goldDustCost = $item->gold_dust_cost;
                $shardsCost   = $item->shards_cost;

                $goldDustCost = $goldDustCost - $goldDustCost * 0.10;
                $shardsCost   = $shardsCost - $shardsCost * 0.10;

                $item->gold_dust_cost = $goldDustCost;
                $item->shards_cost    = $shardsCost;

                return $item;
            });

            if ($showMerchantMessage) {
                event(new GameServerMessageEvent($character->user, 'As a Merchant you get 10% discount on creating alchemy items. The discount has been applied to the items list.'));
            }
        }

        return $items;
    }

    public function transmute(Character $character, int $itemId): void {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY)->first();
        $skill     = Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();
        $item      = Item::find($itemId);

        if (is_null($item)) {
            event(new GameServerMessageEvent($character->user, 'Nope. Item does not exist.'));

            return;
        }

        $goldDustCost = $item->gold_dust_cost;
        $shardsCost   = $item->shards_cost;

        if ($character->classType()->isMerchant()) {
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.10);
            $shardsCost   = floor($shardsCost - $shardsCost * 0.10);

            event( new ServerMessageEvent($character->user, 'As a Merchant you get a 10% reduction on crafting alchemical items.'));
        }

        if ($goldDustCost > $character->gold_dust) {
            event(new ServerMessageEvent($character->user, 'not_enough_gold_dust'));

            return;
        }

        if ($shardsCost > $character->shards) {
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

            event(new UpdateTopBarEvent($character->refresh()));

            return;
        }

        if ($skill->level > $item->skill_level_trivial) {

            event(new ServerMessageEvent($character->user, 'to_easy_to_craft'));

            $this->pickUpItem($character, $item, $skill, true);


            event(new UpdateTopBarEvent($character->refresh()));

            return;
        }

        $characterRoll = $this->characterRoll($skill);
        $dcCheck       = $this->getDCCheck($skill);

        if ($dcCheck < $characterRoll) {
            $this->pickUpItem($character, $item, $skill);


            event(new UpdateTopBarEvent($character->refresh()));

            return;
        }

        event(new ServerMessageEvent($character->user, 'failed_to_transmute'));

        event(new UpdateTopBarEvent($character->refresh()));
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

            $slot = $character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            event(new GameServerMessageEvent($character->user, 'You manage to create: ' . $item->name . ' from gold dust!', $slot->id));

            return true;
        }

        event(new ServerMessageEvent($character->user, 'inventory_full'));

        return false;
    }
}
