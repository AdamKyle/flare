<?php

namespace App\Game\CharacterInventory\Services;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Game\CharacterInventory\Handlers\EquipBest\FetchBestItemForPositionFromInventory;
use App\Game\CharacterInventory\Handlers\EquipBest\HandleHands;
use App\Game\CharacterInventory\Handlers\EquipBest\HandleUniquesAndMythics;
use App\Game\CharacterInventory\Values\EquippablePositions;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Collection;

class EquipBestItemForSlotsTypesService {

    use ResponseBuilder, FetchEquipped;

    private ?Collection $currentlyEquippedSlots;

    private ?Collection $inventorySlots;

    private FetchBestItemForPositionFromInventory $fetchBestItemForPositionFromInventory;

    private HandleHands $handleHands;

    private HandleUniquesAndMythics $handleUniquesAndMythics;

    public function __construct(FetchBestItemForPositionFromInventory $fetchBestItemForPositionFromInventory,
                                HandleHands $handleHands,
                                HandleUniquesAndMythics $handleUniquesAndMythics
    ) {
        $this->fetchBestItemForPositionFromInventory = $fetchBestItemForPositionFromInventory;
        $this->handleHands                           = $handleHands;
        $this->handleUniquesAndMythics               = $handleUniquesAndMythics;
    }

    public function handleBestEquipmentForCharacter(Character $character) {

        $this->fetchInventoryDetails($character);

        if (is_null($this->inventorySlots)) {
            return $this->successResult([
                'message' => 'Inventory is empty. Nothing to equip or replace.'
            ]);
        }

        $this->processPositions($character);
    }

    protected function processPositions(Character $character) {

        $fetchBestItemForPosition = $this->fetchBestItemForPositionFromInventory->setInventory($this->inventorySlots);

        foreach (EquippablePositions::equippablePositions() as $position) {
            $bestSlot = $fetchBestItemForPosition->fetchBestItemForPosition(EquippablePositions::typesForPositions($position));

            if (is_null($bestSlot)) {
                continue;
            }

            if ($bestSlot->item->is_mythic || $bestSlot->item->is_unique) {
                $character = $this->handleUniquesAndMythics->setCurrentlyEquipped($this->currentlyEquippedSlots)
                                                           ->handleUniquesOrMythics($character, $bestSlot, $position);

                $this->fetchInventoryDetails($character);

                if (!$this->handleUniquesAndMythics->replacedSpecialItem()) {
                    continue;
                }

                $slotForReplacedSpecialItem = $fetchBestItemForPosition->fetchBestItemForPosition(
                    EquippablePositions::typesForPositions(
                        $this->handleUniquesAndMythics->getSpecialSlotPosition()
                    ),
                    true,
                );

                if (is_null($slotForReplacedSpecialItem)) {
                    continue;
                }

                $bestSlot = $slotForReplacedSpecialItem;
            }

            if (in_array($position, [EquippablePositions::LEFT_HAND, EquippablePositions::RIGHT_HAND])) {
                $character = $this->handleHands->setCurrentlyEquipped($this->currentlyEquippedSlots)
                                               ->handleHands($character, $bestSlot, $position);


                $this->fetchInventoryDetails($character);

                continue;
            }
        }
    }

    protected function fetchInventoryDetails(Character $character): void {

        $this->currentlyEquipped = $this->fetchEquipped($character);

        $this->inventorySlots    = $character->inventory
            ->slots
            ->where('equipped', false)
            ->whereNotIn('item.type', ['quest', 'alchemy']);
    }

}
