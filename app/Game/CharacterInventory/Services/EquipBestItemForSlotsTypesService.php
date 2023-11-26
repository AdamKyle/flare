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

class EquipBestItemForSlotsTypesService {

    use FetchEquipped, ResponseBuilder;

    private EquipItemService $equipItemService;

    private ItemComparison $itemComparison;

    private Collection $currentlyEquipped;

    private bool $replacedOrEquipped = false;
    private bool $threwError         = false;
    private ?string $errorMessage    = null;

    public function __construct(EquipItemService $equipItemService, ItemComparison $itemComparison) {
        $this->equipItemService = $equipItemService;
        $this->itemComparison   = $itemComparison;
    }

    public function compareAndEquipBestItems(Character $character): array {
        return $this->replaceCurrentlyEquippedItems($character);
    }

    public function replaceCurrentlyEquippedItems(Character $character): array {
        $this->currentlyEquipped = $this->fetchEquipped($character);
        $inventorySlots          = $character->inventory
            ->slots
            ->where('equipped', false)
            ->whereNotIn('item.type', ['quest', 'alchemy']);

        if ($inventorySlots->isEmpty()) {
            return $this->successResult([
                'message' => 'Nothing in your inventory to equip or replace.'
            ]);
        }

        return $this->equipForEachPosition($character, $inventorySlots);
    }

    public function equipForEachPosition(Character $character, Collection $slots): array {

        $equipItemService = $this->equipItemService->setCharacter($character);

        foreach (EquippablePositions::equippablePositions() as $position) {

            if ($this->threwError && !is_null($this->errorMessage)) {
                return $this->errorResult($this->errorMessage);
            }

            $bestSlotForPosition = $this->getBetItemForSpecificPosition($slots, $position);

            if (!is_null($bestSlotForPosition)) {

                if (is_null($this->currentlyEquipped)) {
                    $character = $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);

                    $this->currentlyEquipped = $this->fetchEquipped($character);

                    continue;
                }

                if ($bestSlotForPosition->is_unique) {
                    $result = $this->handleUniqueOrMythic($equipItemService, $character, $slots, $bestSlotForPosition, $position);

                    if (is_null($result)) {
                        continue;
                    }

                }


                if ($bestSlotForPosition->is_mythic) {
                    $result = $this->handleUniqueOrMythic($equipItemService, $character, $slots, $bestSlotForPosition, $position);

                    if (is_null($result)) {
                        continue;
                    }

                    $bestSlotForPosition = $result;
                }

                if ($position === EquippablePositions::LEFT_HAND || $position === EquippablePositions::RIGHT_HAND) {
                    $this->handleHands($equipItemService, $character, $bestSlotForPosition, $position);

                    continue;
                }

                if ($position === EquippablePositions::TRINKET) {
                    $this->handleTrinketsOrArtifacts($equipItemService, $character, $bestSlotForPosition, $position);
                    continue;
                }

                if ($position === EquippablePositions::ARTIFACT) {
                    $this->handleTrinketsOrArtifacts($equipItemService, $character, $bestSlotForPosition, $position);
                    continue;
                }

                $this->handleSwappingEquipment($equipItemService, $character, $bestSlotForPosition, $position);
            }
        }

        $message = 'What you have equipped is either better or the same as anything in your inventory. No changes made.';

        if ($this->replacedOrEquipped) {
            $message = 'Updated character equipment based on inventory.';
        }

        return $this->successResult([
            'message' => $message,
        ]);
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

            $this->equipItemService->unequipSlot($currentlyEquippedTrinket, $inventoryWithTrinket);

            $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);
        }
    }

    protected function handleArtifacts(EquipItemService $equipItemService, Character $character, InventorySlot $bestSlotForPosition, string $position): void {}


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
                                        Collection $slots,
                                        InventorySlot $bestSlotForPosition,
                                        string $position
    ): ?InventorySlot {
        if (($this->doesCharacterHaveUniqueEquipped() || $this->doesCharacterHaveMythicEquipped()) &&
            ($bestSlotForPosition->item->is_unique || $bestSlotForPosition->item->is_mythic)) {

            if ($bestSlotForPosition->item->is_unique) {
                $slotWithSpecial = $this->fetchSlotWithUnique();
            } else {
                $slotWithSpecial = $this->fetchSlotWithMythic();
            }

            $result = $this->handleSpecialItemTypes($equipItemService, $character, $slots, $bestSlotForPosition, $slotWithSpecial, $position);

            if ($result instanceof Collection) {
                $this->currentlyEquipped = $result;

                return null;
            }

            if ($result instanceof InventorySlot) {
                return $result;
            }

            return null;
        }

        return $bestSlotForPosition;
    }

    protected function handleSpecialItemTypes(EquipItemService $equipItemService,
                                              Character $character,
                                              Collection $slots,
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

            $this->equipItemService->unequipSlot($slotWithUniqueOrMythicEquipped, $inventoryForEquippedUnique);

            $character = $this->equipNewBestItem($equipItemService, $character, $bestSlotForPosition, $position);

            return $this->fetchEquipped($character);

        }

        return $this->getBetItemForSpecificPosition($slots, $position, true);
    }

    protected function equipNewBestItem(EquipItemService $equipItemService, Character $character, InventorySlot $bestSlotForPosition, string $position): Character {

        try {
            $equipItemService->setRequest([
                'position' => $position,
                'slot_id' => $bestSlotForPosition->id,
                'equip_type' => $bestSlotForPosition->item->type,
            ])->replaceItem();

        } catch (EquipItemException $e) {
            $this->threwError   = true;
            $this->errorMessage = $e->getMessage();
        }

        return $character->refresh();
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

    protected function getBetItemForSpecificPosition(Collection $slots, string $position, bool $ignoreMythicsAndUniques = false): ?InventorySlot {
        $typesForPosition = EquippablePositions::typesForPositions($position);

        $bestItemsForPosition = $this->findForBestForTypes($slots, $typesForPosition, $ignoreMythicsAndUniques);

        $bestItemForPosition = null;

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

        return $bestItem;
    }

    protected function findForBestForTypes(Collection $slots, array $typesForPosition, bool $ignoreMythicsAndUniques): array {
        $bestItems = [];

        foreach ($typesForPosition as $type) {
            $bestSlot = $slots
                ->filter(function ($slot) use ($type, $ignoreMythicsAndUniques) {

                    if ($ignoreMythicsAndUniques) {
                        return $slot->item->type === $type && !$slot->item->is_unique && !$slot->item->is_mythic;
                    }

                    return $slot->item->type === $type;
                })
                ->reduce(function ($bestItem, $currentItem) {
                    if (!$bestItem) {
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

                if ($value > 0) {
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
