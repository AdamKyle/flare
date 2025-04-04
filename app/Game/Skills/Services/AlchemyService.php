<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Services\Traits\UpdateCharacterCurrency;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class AlchemyService
{
    use ResponseBuilder, UpdateCharacterCurrency;

    private SkillCheckService $skillCheckService;

    private ItemListCostTransformerService $itemListCostTransformerService;

    public function __construct(SkillCheckService $skillCheckService, ItemListCostTransformerService $itemListCostTransformerService)
    {
        $this->skillCheckService = $skillCheckService;
        $this->itemListCostTransformerService = $itemListCostTransformerService;
    }

    public function fetchAlchemistItems(Character $character, bool $showMerchantMessage = true)
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();

        $skill = Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();

        $items = Item::where('can_craft', true)
            ->where('crafting_type', 'alchemy')
            ->where('skill_level_required', '<=', $skill->level)
            ->where('item_prefix_id', null)
            ->where('item_suffix_id', null)
            ->orderBy('skill_level_required', 'asc')
            ->select('id', 'name', 'gold_dust_cost', 'shards_cost', 'type')
            ->get();

        return $this->itemListCostTransformerService->reduceCostOfAlchemyItems($character, $items, $showMerchantMessage);
    }

    public function fetchSkillXP(Character $character): array
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();

        $skill = Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();

        return [
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'skill_name' => $skill->name,
            'level' => $skill->level,
        ];
    }

    public function transmute(Character $character, int $itemId): void
    {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();
        $skill = Skill::where('game_skill_id', $gameSkill->id)->where('character_id', $character->id)->first();
        $item = Item::find($itemId);

        if (is_null($item)) {
            event(new ServerMessageEvent($character->user, 'Nope. Item does not exist.'));

            return;
        }

        $setTime = null;

        if ($character->classType()->isArcaneAlchemist() && $item->crafting_type === 'alchemy') {
            ServerMessageHandler::sendBasicMessage($character->user, 'As a Arcane Alchemist, your crafting timeout for Alchemy items, is reduced by 15%.');

            $setTime = floor(10 - 10 * 0.15);
        }

        event(new CraftedItemTimeOutEvent($character, null, $setTime));

        $goldDustCost = $item->gold_dust_cost;
        $shardsCost = $item->shards_cost;

        if ($character->classType()->isMerchant()) {
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.10);
            $shardsCost = floor($shardsCost - $shardsCost * 0.10);
        }

        if ($character->classType()->isArcaneAlchemist()) {
            $goldDustCost = floor($goldDustCost - $goldDustCost * 0.15);
            $shardsCost = floor($shardsCost - $shardsCost * 0.15);
        }

        if ($goldDustCost > $character->gold_dust) {
            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::NOT_ENOUGH_GOLD_DUST);

            return;
        }

        if ($shardsCost > $character->shards) {
            ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::NOT_ENOUGH_SHARDS);

            return;
        }

        $this->attemptTransmute($character, $skill, $item);
    }

    protected function attemptTransmute(Character $character, Skill $skill, Item $item): void
    {
        $this->updateAlchemyCost($character, $item);

        if ($skill->level < $item->skill_level_required) {

            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_HARD_TO_CRAFT);

            $this->pickUpItem($character, $item, $skill, true);

            $character = $character->refresh();

            event(new UpdateCharacterCurrenciesEvent($character));
            event(new UpdateCharacterInventoryCountEvent($character));

            return;
        }

        if ($skill->level > $item->skill_level_trivial) {

            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::TO_EASY_TO_CRAFT);

            $this->pickUpItem($character, $item, $skill, true);

            $character = $character->refresh();

            event(new UpdateCharacterCurrenciesEvent($character));
            event(new UpdateCharacterInventoryCountEvent($character));

            return;
        }

        $characterRoll = $this->skillCheckService->characterRoll($skill);
        $dcCheck = $this->skillCheckService->getDCCheck($skill);

        if ($dcCheck < $characterRoll) {
            $this->pickUpItem($character, $item, $skill);

            $character = $character->refresh();

            event(new UpdateCharacterCurrenciesEvent($character));
            event(new UpdateCharacterInventoryCountEvent($character));

            return;
        }

        ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::FAILED_TO_TRANSMUTE);

        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));
        event(new UpdateCharacterInventoryCountEvent($character));
    }

    private function pickUpItem(Character $character, Item $item, Skill $skill, bool $tooEasy = false)
    {
        if ($this->attemptToPickUpItem($character, $item)) {

            if (! $tooEasy) {
                event(new UpdateSkillEvent($skill));
            }
        }
    }

    private function attemptToPickUpItem(Character $character, Item $item): bool
    {
        if (! $character->isInventoryFull()) {

            $slot = $character->inventory->slots()->create([
                'item_id' => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You manage to create: ' . $item->name . ' from gold dust!', $slot->id, $slot->item->type));

            return true;
        }

        ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::INVENTORY_IS_FULL);

        return false;
    }
}
