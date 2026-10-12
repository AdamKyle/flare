<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Database\Eloquent\Collection;

class CharacterInventoryActionService
{
    use ResponseBuilder;

    /**
     * @param MassDisenchantService $massDisenchantService
     * @param UpdateCharacterSkillsService $updateCharacterSkillsService
     * @param DisenchantService $disenchantService
     */
    public function __construct(
        private readonly MassDisenchantService $massDisenchantService,
        private readonly UpdateCharacterSkillsService $updateCharacterSkillsService,
        private readonly DisenchantService $disenchantService,
    ) {}

    /**
     * Disenchant all supplied slots and report their rewards.
     *
     * @param Collection $slots
     * @param Character $character
     * @return array
     */
    public function disenchantAll(Collection $slots, Character $character): array
    {
        $maxedOutGoldDust = $character->gold_dust >= CurrencyLimit::MAX_GOLD_DUST;
        $this->massDisenchantService->setUp($character)->disenchantItems($slots);
        $disenchantingLevels = $this->massDisenchantService->getDisenchantingTimesLeveled();
        $enchantingLevels = $this->massDisenchantService->getEnchantingTimesLeveled();
        $goldDust = $this->massDisenchantService->getTotalGoldDust();
        $this->updateCharacterSkillsService->updateCharacterCraftingSkills($character->refresh());

        $message = 'Disenchanted all items and gained: '.($maxedOutGoldDust ? 0 .' (You are capped ) ' : number_format($goldDust)).' Gold Dust (with gold dust rushes)';
        $message .= $disenchantingLevels > 0 ? ' You also gained: '.$disenchantingLevels.' Skill Levels in Disenchanting.' : '';
        $message .= $enchantingLevels > 0 ? ' You also gained: '.$enchantingLevels.' Skill Levels in Enchanting.' : '';

        return $this->successResult(['message' => $message]);
    }

    /**
     * Destroy one unequipped normal Inventory Item.
     *
     * @param Character $character
     * @param int $itemId
     * @return array
     */
    public function deleteItem(Character $character, int $itemId): array
    {
        $slot = $this->destroyableSlot($character, $itemId);

        if (is_null($slot)) {
            return $this->errorResult('No matching item to destroy.');
        }

        $name = $slot->item->affix_name;
        $slot->delete();
        event(new UpdateCharacterInventoryCountEvent($character->refresh()));

        return $this->successResult(['message' => 'Destroyed item: '.$name.'.']);
    }

    /**
     * Destroy every eligible normal Inventory Item.
     *
     * @param Character $character
     * @return array
     */
    public function destroyAllItems(Character $character): array
    {
        $slotIds = $character->inventory->slots
            ->whereNotIn('item.type', ['quest', 'alchemy', 'gem', 'artifact'])
            ->where('equipped', false)
            ->pluck('id')
            ->all();
        $character->inventory->slots()->whereIn('id', $slotIds)->delete();
        event(new UpdateCharacterInventoryCountEvent($character->refresh()));

        return $this->successResult(['message' => 'Destroyed all items.']);
    }

    /**
     * Disenchant every eligible normal Inventory Item.
     *
     * @param Character $character
     * @return array
     */
    public function disenchantAllInventoryItems(Character $character): array
    {
        $slots = $character->inventory->slots
            ->where('equipped', false)
            ->filter(fn ($slot): bool => ! in_array($slot->item->type, ['quest', 'alchemy', 'artifact'], true)
                && (! is_null($slot->item->item_prefix_id) || ! is_null($slot->item->item_suffix_id)))
            ->values();

        return $slots->isEmpty()
            ? $this->successResult(['message' => 'You have nothing to disenchant.'])
            : $this->disenchantAll(new Collection($slots), $character);
    }

    /**
     * Destroy one owned Alchemy Bag Item.
     *
     * @param Character $character
     * @param int $slotId
     * @return array
     */
    public function destroyAlchemyItem(Character $character, int $slotId): array
    {
        $slot = is_null($character->alchemyBag) ? null : AlchemyBagSlot::where('id', $slotId)
            ->where('character_id', $character->id)
            ->where('alchemy_bag_id', $character->alchemyBag->id)
            ->with('item')
            ->first();

        if (is_null($slot)) {
            return $this->errorResult('No alchemy item found to destroy.');
        }

        if (! is_null($slot->item->currency_cache_type)) {
            return $this->errorResult('Compensation Caches cannot be destroyed. They can only be used.');
        }

        $name = $slot->item->affix_name;
        $slot->delete();
        $character = $character->refresh();
        event(new UpdateCharacterBaseDetailsEvent($character));
        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult(['message' => 'Destroyed Alchemy Item: '.$name.'.']);
    }

    /**
     * Destroy every owned Alchemy Bag Item except Compensation Caches.
     *
     * @param Character $character
     * @return array
     */
    public function destroyAllAlchemyItems(Character $character): array
    {
        if (! is_null($character->alchemyBag)) {
            AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)
                ->where('character_id', $character->id)
                ->whereDoesntHave('item', fn ($query) => $query->whereNotNull('currency_cache_type'))
                ->delete();
        }

        $character = $character->refresh();
        event(new UpdateTopBarEvent($character));
        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult(['message' => 'Destroyed All Alchemy Items.']);
    }

    /**
     * Sell one unequipped normal Inventory Item.
     *
     * @param Character $character
     * @param int $itemId
     * @return array
     */
    public function sellItem(Character $character, int $itemId): array
    {
        $slot = $this->destroyableSlot($character, $itemId);

        if (is_null($slot)) {
            return $this->errorResult('No item found to be sell.');
        }

        $name = $slot->item->affix_name;
        $gold = SellItemCalculator::fetchSalePriceWithAffixes($slot->item);
        event(new SellItemEvent($slot, $character));

        return $this->successResult(['message' => 'Sold '.$name.' for a total of '.number_format($gold, 0).' gold.']);
    }

    /**
     * Disenchant one unequipped normal Inventory Item.
     *
     * @param Character $character
     * @param int $itemId
     * @return array
     */
    public function disenchantItem(Character $character, int $itemId): array
    {
        $slot = $this->destroyableSlot($character, $itemId);

        return is_null($slot)
            ? $this->errorResult('No item found to disenchant.')
            : $this->disenchantService->setUp($character)->disenchantItem($slot);
    }

    /**
     * Resolve one unequipped normal Inventory slot.
     *
     * @param Character $character
     * @param int $itemId
     * @return mixed
     */
    private function destroyableSlot(Character $character, int $itemId): mixed
    {
        return $character->inventory->slots()
            ->whereHas('item', fn ($query) => $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']))
            ->where('equipped', false)
            ->where('item_id', $itemId)
            ->first();
    }
}
