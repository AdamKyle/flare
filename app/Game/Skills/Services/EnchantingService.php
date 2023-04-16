<?php

namespace App\Game\Skills\Services;

use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Game\Skills\Services\Traits\UpdateCharacterCurrency;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Database\Eloquent\Collection;

class EnchantingService {

    use ResponseBuilder, UpdateCharacterCurrency;

    /**
     * @var CharacterStatBuilder $characterStatBuilder;
     */
    private CharacterStatBuilder $characterStatBuilder;

    /**
     * @var CharacterInventoryService $characterInventoryService
     */
    private CharacterInventoryService $characterInventoryService;

    /**
     * @var EnchantItemService $enchantItemService
     */
    private EnchantItemService $enchantItemService;

    /**
     * @var RandomEnchantmentService
     */
    private RandomEnchantmentService $randomEnchantmentService;

    /**
     * @var bool $sentToEasyMessage
     */
    private bool $sentToEasyMessage = false;

    /**
     * Only set if the affix to be applied was too easy to enchant.
     *
     * @var bool $wasTooEasy
     */
    private $wasTooEasy = false;

    /**
     * Constructor
     *
     * @param CharacterStatBuilder $characterStatBuilder
     * @param CharacterInventoryService $characterInventoryService
     * @param EnchantItemService $enchantItemService
     * @param RandomEnchantmentService $randomEnchantmentService
     */
    public function __construct(CharacterStatBuilder $characterStatBuilder,
                                CharacterInventoryService $characterInventoryService,
                                EnchantItemService $enchantItemService,
                                RandomEnchantmentService $randomEnchantmentService)
    {

        $this->characterStatBuilder        = $characterStatBuilder;
        $this->characterInventoryService   = $characterInventoryService;
        $this->enchantItemService          = $enchantItemService;
        $this->randomEnchantmentService    = $randomEnchantmentService;
    }

    /**
     * Fetches the affixes for a character.
     *
     * Only returns that which the player has the skill level and intelligence for.
     *
     * @param Character $character
     * @param bool $ignoreTrinkets
     * @param bool $showMerchantMessage
     * @return array
     */
    public function fetchAffixes(Character $character, bool $ignoreTrinkets = false, bool $showMerchantMessage = true): array {
        $characterInfo   = $this->characterStatBuilder->setCharacter($character);
        $enchantingSkill = $this->getEnchantingSkill($character);

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);
        $inventory                 = $characterInventoryService->getInventoryForType('inventory');

        if ($ignoreTrinkets) {
            $inventory = array_values(array_filter($inventory, function($item) {
                return $item['type'] !== 'trinket';
            }));
        }

        $newInventory = [];

        foreach ($inventory as $item) {
            if ($item['attached_affixes_count'] === 0) {
                array_unshift($newInventory, $item);
            } else {
                $newInventory[] = $item;
            }
        }

        return [
            'affixes'             => $this->getAvailableAffixes($characterInfo, $enchantingSkill, $showMerchantMessage),
            'character_inventory' => $newInventory,
        ];
    }

    /**
     * Does the cost supplied actually match the actual cost?
     *
     * @param Character $character
     * @param array $enchantmentIds
     * @param int $itemId
     * @return int
     * @throws Exception
     */
    public function getCostOfEnchantment(Character $character, array $enchantmentIds, int $itemId): int {
        $itemAffixes   = ItemAffix::findMany($enchantmentIds);
        $itemToEnchant = Item::find($itemId);

        if ($itemAffixes->isEmpty()) {
            return 0;
        }

        if (is_null($itemToEnchant)) {
            return 0;
        }

        $cost = $itemAffixes->sum('cost');

        foreach ($itemAffixes as $itemAffix) {
            if (!is_null($itemToEnchant->{'item_' . $itemAffix->type . '_id'})) {
                $cost += 1000;
            }
        }

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.15);

            ServerMessageHandler::sendBasicMessage($character->user, 'As a Merchant you get a 15% reduction on enchanting items (reduction applied to total price).');
        }

        return $cost;
    }

    /**
     * Enchant an item.
     *
     * Attempts to enchant an item with the supplied affixes and slot.
     *
     * The params passed in must be the request params coming back from the request.
     *
     * The array returned contains the status and the details, either a list of
     * the characters inventory and their affixes they can enchant or a error message.
     *
     * eg, ['message' => '', 'status' => 422] or
     * ['affixes' => Collection, 'character_inventory' => [...], 'status' => 200]
     *
     * @param Character $character
     * @param array $params
     * @param InventorySlot $slot
     * @param int $cost
     * @return void
     * @throws Exception
     */
    public function enchant(Character $character, array $params, InventorySlot $slot, int $cost): void {
        $enchantingSkill = $this->getEnchantingSkill($character);

        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        $character = $character->refresh();

        $this->attachAffixes($params['affix_ids'], $slot, $enchantingSkill, $character);

        $this->enchantItemService->updateSlot($slot);
    }

    public function timeForEnchanting(Item $item) {

        if (!is_null($item->itemPrefix) && !is_null($item->itemSuffix)) {
            return 'triple';
        }

        if (!is_null($item->itemPrefix) || !is_null($item->itemSuffix)) {
            return 'double';
        }

        return null;
    }

    public function getSlotFromInventory(Character $character, int $slotId) {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('id', $slotId)->where('inventory_id', $inventory->id)->where('equipped', false)->first();
    }

    protected function getEnchantingSkill(Character $character): Skill {
        $gameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING)->first();

        return Skill::where('character_id', $character->id)->where('game_skill_id', $gameSkill->id)->first();
    }

    protected function getAvailableAffixes(CharacterStatBuilder $builder, Skill $enchantingSkill, bool $showMerchantMessage = true): Collection {

        $currentInt = $builder->statMod('int');

        $affixes = ItemAffix::select('name', 'cost', 'id', 'type')
                            ->where('int_required', '<=', $currentInt)
                            ->where('skill_level_required', '<=', $enchantingSkill->level)
                            ->where('randomly_generated', false)
                            ->orderBy('skill_level_required', 'asc')
                            ->get();

        $character = $builder->character();

        if ($character->classType()->isMerchant() && $showMerchantMessage) {

            event(new ServerMessageEvent($character->user, 'As a Merchant you get 15% discount on enchanting items. This discount is applied to the total cost of the enchantments, not the individual enchantments.'));
        }

        return $affixes;
    }

    protected function attachAffixes(array $affixes, InventorySlot $slot, Skill $enchantingSkill, Character $character) {
        foreach ($affixes as $affixId) {
            $slot  = $slot->refresh();

            $affix = ItemAffix::find($affixId);

            if (is_null($affix)) {
                continue;
            }

            // Reset.
            $this->wasTooEasy = false;

            if ($enchantingSkill->level < $affix->skill_level_required) {
                ServerMessageHandler::handleMessage($character->user, 'to_hard_to_craft');

                return;
            }

            if ($enchantingSkill->level > $affix->skill_level_trivial) {
                if (!$this->sentToEasyMessage) {
                    ServerMessageHandler::handleMessage($character->user, 'to_easy_to_craft');

                    $this->sentToEasyMessage = true;
                }

                $this->processedEnchant($slot, $affix, $character, $enchantingSkill, true);

                $this->wasTooEasy = true;
            }

            /**
             * If the affix wasn't too easy to attach, attempt to enchant with the difficulty check
             * in place.
             *
             * If we fail to do this then we return from the loop.
             */
            if (!$this->wasTooEasy) {
                if (!$this->processedEnchant($slot, $affix, $character, $enchantingSkill)) {

                    return;
                }
            }
        }
    }

    protected function processedEnchant(InventorySlot $slot, ItemAffix $affix, Character $character, Skill $enchantingSkill, bool $tooEasy = false) {
        $enchanted = $this->enchantItemService->attachAffix($slot->item, $affix, $enchantingSkill, $tooEasy);

        if ($enchanted) {
            $this->appliedEnchantment($slot, $affix, $character, $enchantingSkill, $tooEasy);
        } else {
            $this->failedToApplyEnchantment($slot, $affix, $character);

            return false;
        }

        return true;
    }

    protected function appliedEnchantment(InventorySlot $slot, ItemAffix $affix, Character $character, Skill $enchantingSkill, bool $tooEasy = false) {
        $message = 'Applied enchantment: '.$affix->name.' to: ' . $slot->item->refresh()->affix_name;

        ServerMessageHandler::handleMessage($character->user, 'enchanted', $message, $slot->id);

        if (!$tooEasy) {
            event(new UpdateSkillEvent($enchantingSkill));
        }
    }

    protected function failedToApplyEnchantment(InventorySlot $slot, ItemAffix $affix, Character $character) {
        $message = 'You failed to apply '.$affix->name.' to: ' . $slot->item->refresh()->affix_name . '. The item shatters before you. You lost the investment.';

        ServerMessageHandler::handleMessage($character->user, 'enchantment_failed', $message);

        $this->enchantItemService->deleteSlot($slot);
    }
}
