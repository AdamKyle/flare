<?php

namespace App\Game\CharacterInventory\Services;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Values\WeaponTypes;
use App\Game\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Collection;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Game\CharacterInventory\Values\EquippablePositions;
use Illuminate\Support\Facades\Log;

class EquipBestItemForSlotsTypesServiceBack {

    use FetchEquipped, ResponseBuilder;

    private EquipItemService $equipItemService;

    private ItemComparison $itemComparison;

    private ?Collection $currentlyEquipped = null;

    private Collection $inventorySlots;

    private bool $replacedOrEquipped = false;
    private bool $threwError         = false;

    public function __construct(EquipItemService $equipItemService, ItemComparison $itemComparison) {
        $this->equipItemService = $equipItemService;
        $this->itemComparison   = $itemComparison;
    }

    public function compareAndEquipBestItems(Character $character): array {
        return $this->replaceCurrentlyEquippedItems($character);
    }

    public function replaceCurrentlyEquippedItems(Character $character): array {
        $this->currentlyEquipped = $this->fetchEquipped($character);
        $this->inventorySlots    = $character->inventory
            ->slots
            ->where('equipped', false)
            ->whereNotIn('item.type', ['quest', 'alchemy']);

        if ($this->inventorySlots->isEmpty()) {
            return $this->successResult([
                'message' => 'Nothing in your inventory to equip or replace.'
            ]);
        }

        return $this->equipForEachPosition($character);
    }

    public function equipForEachPosition(Character $character): array {

        $equipItemService = $this->equipItemService->setCharacter($character);

        foreach (EquippablePositions::equippablePositions() as $position) {

            if ($this->threwError) {
                return $this->errorResult(
                    'An error has occurred when attempting to figure out what to equip.' . ' ' .
                    'This has been logged. When posting in discord, please also post your character ' .
                    'name to make searching the logs easier.'
                );
            }

            $this->handlePossibleReplaceOrEquipOfPosition($character, $equipItemService, $position);
        }

        $message = 'What you have equipped is either better or the same as anything in your inventory. No changes made.';

        if ($this->replacedOrEquipped) {
            $message = 'Updated character equipment based on inventory.';
        }

        return $this->successResult([
            'message' => $message,
        ]);
    }

    protected function handlePossibleReplaceOrEquipOfPosition(Character $character, EquipItemService $equipItemService, string $position) {
        $bestSlotForPosition = $this->getBetItemForSpecificPosition($position);

        if (!is_null($bestSlotForPosition)) {

            if (is_null($this->currentlyEquipped)) {
                $character = $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);

                $this->currentlyEquipped = $this->fetchEquipped($character);

                return;
            }

            if ($bestSlotForPosition->item->is_unique || $bestSlotForPosition->item->is_mythic) {
                $result = $this->handleUniqueOrMythic($equipItemService, $character, $bestSlotForPosition, $position);

                if (is_null($result)) {
                    return;
                }

                $bestSlotForPosition = $result;
            }

            if ($position === EquippablePositions::LEFT_HAND || $position === EquippablePositions::RIGHT_HAND) {

                $this->handleHands($equipItemService, $character, $bestSlotForPosition, $position);

                return;
            }

            if ($position === EquippablePositions::TRINKET) {

                $this->handleTrinketsOrArtifacts($equipItemService, $character, $bestSlotForPosition, $position);

                return;
            }

            if ($position === EquippablePositions::ARTIFACT) {

                $this->handleTrinketsOrArtifacts($equipItemService, $character, $bestSlotForPosition, $position);

                return;
            }

            $this->handleSwappingEquipment($equipItemService, $character, $bestSlotForPosition, $position);
        } else {
            dump('Nothing found for Position: ' . $position);
        }
    }

    protected function handleSwappingEquipment(EquipItemService $equipItemService, Character $character, InventorySlot $bestSlotForPosition, string $position): void {

        $slotCurrentlyEquippedForPosition = $this->currentlyEquipped->where('position', $position)->first();

        if (is_null($slotCurrentlyEquippedForPosition)) {
            $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);

            return;
        }

        if ($this->compareItems($bestSlotForPosition->item, $slotCurrentlyEquippedForPosition->item)) {
            $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);
        }
    }

    protected function handleTrinketsOrArtifacts(EquipItemService $equipItemService, Character $character, InventorySlot $bestSlotForPosition, string $position): void {
        $currentlyEquippedTrinket = $this->currentlyEquipped->where('position', $position)->first();

        if (is_null($currentlyEquippedTrinket)) {
            $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);

            return;
        }

        if ($this->compareItems($bestSlotForPosition->item, $currentlyEquippedTrinket->item)) {

            if ($currentlyEquippedTrinket instanceof InventorySlot) {
                $inventoryWithTrinket = $currentlyEquippedTrinket->inventory;
            } else {
                $inventoryWithTrinket = $currentlyEquippedTrinket->inventorySet;
            }

            $this->equipItemService->setRequest([
                'position' => $currentlyEquippedTrinket->position
            ])->unequipSlot($currentlyEquippedTrinket, $inventoryWithTrinket);

            $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);
        }
    }

    protected function handleHands(EquipItemService $equipItemService, Character $character, InventorySlot $bestSlotForPosition, string $position): void {
        $twoHanded = [WeaponTypes::HAMMER, WeaponTypes::BOW, WeaponTypes::STAVE];
        $oppositePosition = EquippablePositions::getOppisitePosition($position);

        if (is_null($oppositePosition)) {
            return;
        }

        $slotForOppositePosition = $this->currentlyEquipped->where('position', $oppositePosition)->first();

        if (is_null($slotForOppositePosition)) {
            return;
        }

        $itemTypeForPosition = $slotForOppositePosition->item->type;

        if (!in_array($itemTypeForPosition, $twoHanded)) {
            return;
        }

        if ($this->compareItems($bestSlotForPosition->item, $slotForOppositePosition->item)) {
            $equipItemService->unequipBothHands();

            $equipItemService->setRequest([
                'position' => $position,
                'slot_id' => $bestSlotForPosition->id,
                'equip_type' => $bestSlotForPosition->item->type,
            ])->replaceItem();

            $character = $character->refresh();

            $this->currentlyEquipped = $this->fetchEquipped($character);
        }
    }

    protected function handleUniqueOrMythic(EquipItemService $equipItemService,
                                        Character $character,
                                        InventorySlot $bestSlotForPosition,
                                        string $position
    ): ?InventorySlot {

        $hasUnique = $this->doesCharacterHaveUniqueEquipped();
        $hasMythic = $this->doesCharacterHaveMythicEquipped();

        if ($hasMythic && $bestSlotForPosition->item->is_unique) {
            return null;
        }

        if ($hasMythic && $bestSlotForPosition->item->is_mythic) {
            return $this->handleReplacingUniqueOrMythic(
                $equipItemService,
                $character,
                $bestSlotForPosition,
                $position,
                true
            );
        }

        if ($hasUnique && $bestSlotForPosition->item->is_mythic) {
            return $this->handleReplacingUniqueOrMythic(
                $equipItemService,
                $character,
                $bestSlotForPosition,
                $position,
                true
            );
        }

        if ($hasUnique && $bestSlotForPosition->item->is_unique) {
            return $this->handleReplacingUniqueOrMythic(
                $equipItemService,
                $character,
                $bestSlotForPosition,
                $position,
                true
            );
        }

        if ($bestSlotForPosition->item->is_unique || $bestSlotForPosition->item->is_mythic) {
            dump($this->currentlyEquipped->pluck('item.affix_name', 'item.type')->toArray(), $bestSlotForPosition->item->affix_name);
        }

        return $bestSlotForPosition;
    }

    protected function handleReplacingUniqueOrMythic(EquipItemService $equipItemService,
                                                     Character $character,
                                                     InventorySlot $bestSlotForPosition,
                                                     string $position,
                                                     bool $replaceMythic = false): ?InventorySlot {

        if ($replaceMythic) {
            $slotWithSpecial = $this->fetchSlotWithMythic();
        } else {
            $slotWithSpecial = $this->fetchSlotWithUnique();
        }

        $positionToReplace = $slotWithSpecial->position;
        $result            = $this->handleSpecialItemTypes($equipItemService, $character, $bestSlotForPosition, $slotWithSpecial, $position);

        if ($result instanceof Collection) {
            $this->currentlyEquipped = $result;

            $this->handlePossibleReplaceOrEquipOfPosition($character, $equipItemService, $positionToReplace);

            return null;
        }

        if ($result instanceof InventorySlot) {
            return $result;
        }

        return null;
    }

    protected function handleSpecialItemTypes(EquipItemService $equipItemService,
                                              Character $character,
                                              InventorySlot $bestSlotForPosition,
                                              InventorySlot|SetSlot $slotWithUniqueOrMythicEquipped,
                                              string $position
                                              ): Collection | InventorySlot | null
    {
        if ($this->compareItems($bestSlotForPosition->item, $slotWithUniqueOrMythicEquipped->item)) {

            if ($slotWithUniqueOrMythicEquipped instanceof InventorySlot) {
                $inventoryForEquippedUnique = $slotWithUniqueOrMythicEquipped->inventory;
            } else {
                $inventoryForEquippedUnique = $slotWithUniqueOrMythicEquipped->inventorySet;
            }

            $this->equipItemService->setRequest([
                'position' => $slotWithUniqueOrMythicEquipped->position,
            ])->unequipSlot($slotWithUniqueOrMythicEquipped, $inventoryForEquippedUnique);

            $character = $character->refresh();

            $character = $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);

            $this->inventorySlots    = $character->inventory
                ->slots
                ->where('equipped', false)
                ->whereNotIn('item.type', ['quest', 'alchemy']);

            return $this->fetchEquipped($character);

        }

        $bestSlot = $this->getBetItemForSpecificPosition($position, true);

        return $bestSlot;
    }

    protected function equipNewBestItem(EquipItemService $equipItemService, Character $character, InventorySlot $bestSlotForPosition, string $position): Character {

        try {
            $equipItemService->setRequest([
                'position' => $position,
                'slot_id' => $bestSlotForPosition->id,
                'equip_type' => $bestSlotForPosition->item->type,
            ])->replaceItem();

        } catch (EquipItemException $e) {

            Log::channel('equip_best')->info('======= [ Best Equipment Issue] =======');
            Log::channel('equip_best')->info('BEST SLOT FOR POSITION:');
            Log::channel('equip_best')->info(json_decode(json_encode($bestSlotForPosition)));

            Log::channel('equip_best')->info('');

            Log::channel('equip_best')->info('=== ERROR DETAILS ===');
            Log::channel('equip_best')->info($e->getMessage());

            Log::channel('equip_best')->info('======================================');
            Log::channel('equip_best')->info('');
            Log::channel('equip_best')->info('');

            $this->threwError   = true;
        }

        $character = $character->refresh();

        $this->inventorySlots = $character->inventory
            ->slots
            ->where('equipped', false)
            ->whereNotIn('item.type', ['quest', 'alchemy']);

        return $character;
    }

    protected function doesCharacterHaveUniqueEquipped(): bool {
        return $this->currentlyEquipped->where('item.is_unique', true)->isNotEmpty();
    }

    protected function fetchSlotWithUnique(): InventorySlot|SetSlot {
        return $this->currentlyEquipped->where('item.is_unique', true)->first();
    }

    protected function doesCharacterHaveMythicEquipped(): bool {
        return $this->currentlyEquipped->where('item.is_mythic', true)->isNotEmpty();
    }

    protected function fetchSlotWithMythic(): InventorySlot|SetSlot {
        return $this->currentlyEquipped->where('item.is_mythic', true)->first();
    }

    protected function getBetItemForSpecificPosition(string $position, bool $ignoreMythicsAndUniques = false): ?InventorySlot {
        $typesForPosition = EquippablePositions::typesForPositions($position);

        $bestItemsForPosition = $this->findForBestForTypes($typesForPosition, $ignoreMythicsAndUniques);

        $bestItemForPosition = null;

        if (count($bestItemsForPosition) === 1) {
            return $bestItemsForPosition[0];
        }

        foreach ($bestItemsForPosition as $index => $bestItem) {
            $nextSlot = null;

            if (!isset($bestItemsForPosition[$index + 1])) {
                continue;
            }

            $nextSlot = $bestItemsForPosition[$index + 1];

            if (!is_null($bestItemForPosition)) {
                if ($this->compareItems($nextSlot->item, $bestItem->item)) {
                    $bestItemForPosition = $nextSlot;

                    continue;
                }

                continue;
            }

            if ($this->compareItems($bestItem->item, $nextSlot->item)) {
                $bestItemForPosition = $bestItem;

                continue;
            }

            if ($this->compareItems($nextSlot->item, $bestItem->item)) {
                $bestItemForPosition = $nextSlot;
            }
        }

        return $bestItemForPosition;
    }

    protected function findForBestForTypes(array $typesForPosition, bool $ignoreMythicsAndUniques): array {
        $bestItems = [];

        foreach ($typesForPosition as $type) {
            $bestSlot = $this->inventorySlots
                ->filter(function ($slot) use ($type, $ignoreMythicsAndUniques) {

                    if ($ignoreMythicsAndUniques) {
                        return $slot->item->type === $type && !$slot->item->is_unique && !$slot->item->is_mythic;
                    }

                    return $slot->item->type === $type;
                })
                ->reduce(function ($bestItem, $currentItem) {

                    if (is_null($bestItem)) {
                        return $currentItem;
                    }

                    return $this->compareItems($bestItem->item, $currentItem->item) ? $bestItem : $currentItem;
                });

            if (!is_null($bestSlot)) {
                $bestItems[] = $bestSlot;
            }
        }

        return $bestItems;
    }

    private function compareItems(Item $item1, Item $item2) {
        $result = $this->itemComparison->fetchItemComparisonDetails($item1, $item2);

        return $this->isItemGood($result);
    }

    private function isItemGood(array $item) {

        $goodCount = 0;
        $reductions = 0;

        foreach ($item as $key => $value) {

            if (is_numeric($value)) {

                if ($value >= 0) {
                    $goodCount++;
                } else {
                    $reductions++;
                }
            }
        }

        if ($goodCount === 0) {
            return false;
        }

        return $goodCount > $reductions;
    }
}
