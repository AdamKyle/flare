<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use Exception;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;

class EnchantItemService
{
    private ?Item $item = null;

    private int $dcIncrease = 0;

    private SkillCheckService $skillCheckService;

    private HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingEnchantingGlobalEventGoal;

    public function __construct(SkillCheckService $skillCheckService, HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingEnchantingGlobalEventGoal)
    {
        $this->skillCheckService = $skillCheckService;
        $this->handleUpdatingEnchantingGlobalEventGoal = $handleUpdatingEnchantingGlobalEventGoal;
    }

    /**
     * Attach the affix to the item
     */
    public function attachAffix(Item $item, ItemAffix $affix, Skill $enchantingSkill, bool $tooEasy = false): bool
    {
        if ($tooEasy) {
            $this->enchantItem($item, $affix);
        } else {
            $dcCheck = $this->skillCheckService->getDCCheck($enchantingSkill, $this->dcIncrease);
            $characterRoll = $this->skillCheckService->characterRoll($enchantingSkill);

            if ($dcCheck > $characterRoll) {
                return false;
            } else {
                $this->enchantItem($item, $affix);
            }
        }

        return true;
    }

    /**
     * Update the slot.
     *
     * @throws Exception
     */
    public function updateSlot(InventorySlot|GlobalEventCraftingInventorySlot $slot, bool $enchantForEvent): void
    {
        if (! is_null($this->item)) {

            if ($this->item->appliedHolyStacks->isEmpty() && $this->item->sockets->isEmpty()) {
                if ($this->getCountOfMatchingItems() > 1) {
                    $slot->update([
                        'item_id' => $this->findMatchingItemId(),
                    ]);
                } else {
                    $slot->update([
                        'item_id' => $this->item->id,
                    ]);

                    $slot = $slot->refresh();
                }
            } else {
                $slot->update([
                    'item_id' => $this->item->id,
                ]);

                $slot = $slot->refresh();
            }

            if ($enchantForEvent) {
                $character = $slot->inventory->character;

                $this->handleUpdatingEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);
            }
        }
    }

    /**
     * Delete the slot.
     */
    public function deleteSlot(InventorySlot $slot): void
    {
        $slot->delete();

        if (! is_null($this->item)) {
            $this->item->delete();

            $this->item = null;
        }
    }

    /**
     * Get the item.
     */
    public function getItem(): ?Item
    {
        return $this->item;
    }

    /**
     * Enchant the item.
     *
     * @return void
     */
    protected function enchantItem(Item $item, ItemAffix $affix)
    {
        if (! is_null($this->item)) {
            $this->cloneItem($this->item, $affix);

            return;
        }

        $this->cloneItem($item, $affix);
    }

    protected function cloneItem(Item $item, ItemAffix $affix)
    {
        $clonedItem = DuplicateItemHandler::duplicateItem($item);

        $clonedItem->{'item_' . $affix->type . '_id'} = $affix->id;
        $clonedItem->market_sellable = true;
        $clonedItem->parent_id = $item->id;
        $clonedItem->is_mythic = false;
        $clonedItem->is_cosmic = false;

        if ($affix->type === 'suffix') {

            if (! is_null($clonedItem->itemSuffix)) {
                if ($clonedItem->itemSuffix->cost === RandomAffixDetails::MYTHIC) {
                    $clonedItem->item_suffix_id = null;
                }

                if ($clonedItem->itemSuffix->cost === RandomAffixDetails::COSMIC) {
                    $clonedItem->item_suffix_id = null;
                }
            }
        }

        if ($affix->type === 'prefix') {

            if (! is_null($clonedItem->itemPrefix)) {
                if ($clonedItem->itemPrefix->cost === RandomAffixDetails::MYTHIC) {
                    $clonedItem->item_prefix_id = null;
                }

                if ($clonedItem->itemPrefix->cost === RandomAffixDetails::COSMIC) {
                    $clonedItem->item_prefix_id = null;
                }
            }
        }

        $clonedItem->save();

        $this->item = $clonedItem->refresh();
    }

    /**
     * Count the matching items.
     */
    protected function getCountOfMatchingItems(): int
    {
        return Item::where('name', $this->item->name)
            ->where('item_prefix_id', $this->item->item_prefix_id)
            ->where('item_suffix_id', $this->item->item_suffix_id)
            ->whereDoesntHave('appliedHolyStacks')
            ->whereDoesntHave('sockets')
            ->count();
    }

    /**
     * Fetch matching item id.
     */
    protected function findMatchingItemId(): int
    {
        $item = $this->item;

        $this->item->delete();
        $this->item = null;

        return Item::where('name', $item->name)
            ->where('item_prefix_id', $item->item_prefix_id)
            ->where('item_suffix_id', $item->item_suffix_id)
            ->whereDoesntHave('appliedHolyStacks')
            ->whereDoesntHave('sockets')
            ->first()
            ->id;
    }
}
