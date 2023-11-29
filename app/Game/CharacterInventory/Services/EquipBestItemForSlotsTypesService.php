<?php

namespace App\Game\CharacterInventory\Services;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Game\CharacterInventory\Handlers\EquipBest\FetchBestItemForPositionFromInventory;
use App\Game\CharacterInventory\Handlers\EquipBest\HandleHands;
use App\Game\CharacterInventory\Handlers\EquipBest\HandleRegularComparisonAndReplace;
use App\Game\CharacterInventory\Handlers\EquipBest\HandleTrinketsAndArtifacts;
use App\Game\CharacterInventory\Handlers\EquipBest\HandleUniquesAndMythics;
use App\Game\CharacterInventory\Values\EquippablePositions;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Collection;

class EquipBestItemForSlotsTypesService {

    use ResponseBuilder, FetchEquipped;

    private ?Collection $currentlyEquippedSlots = null;

    private ?Collection $inventorySlots = null;

    private bool $equipmentHasChanged = false;

    private FetchBestItemForPositionFromInventory $fetchBestItemForPositionFromInventory;

    private HandleHands $handleHands;

    private HandleUniquesAndMythics $handleUniquesAndMythics;

    private HandleTrinketsAndArtifacts $handleTrinketsAndArtifacts;

    private HandleRegularComparisonAndReplace $handleRegularComparisonAndReplace;

    public function __construct(FetchBestItemForPositionFromInventory $fetchBestItemForPositionFromInventory,
                                HandleHands $handleHands,
                                HandleUniquesAndMythics $handleUniquesAndMythics,
                                HandleTrinketsAndArtifacts $handleTrinketsAndArtifacts,
                                HandleRegularComparisonAndReplace $handleRegularComparisonAndReplace
    ) {
        $this->fetchBestItemForPositionFromInventory = $fetchBestItemForPositionFromInventory;
        $this->handleHands                           = $handleHands;
        $this->handleUniquesAndMythics               = $handleUniquesAndMythics;
        $this->handleTrinketsAndArtifacts            = $handleTrinketsAndArtifacts;
        $this->handleRegularComparisonAndReplace     = $handleRegularComparisonAndReplace;
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

    public function hasEquipmentChanged(): bool {
        return $this->equipmentHasChanged;
    }

    protected function processPositions(Character $character): void {

        $this->fetchBestItemForPositionFromInventory = $this->fetchBestItemForPositionFromInventory->setInventory($this->inventorySlots);

        foreach (EquippablePositions::equippablePositions() as $position) {
            $bestSlot = $this->fetchBestItemForPositionFromInventory->fetchBestItemForPosition(EquippablePositions::typesForPositions($position));

            if (is_null($bestSlot)) {
                continue;
            }

            dump($bestSlot->id . ' ' . $bestSlot->item->affix_name . ' ' . $position);

            if ($bestSlot->item->is_mythic || $bestSlot->item->is_unique) {

                $character = $this->handleUniquesAndMythics->setCurrentlyEquipped($this->currentlyEquippedSlots)
                                                           ->handleUniquesOrMythics($character, $position, $bestSlot);

                $this->fetchInventoryDetails($character);

                if (!$this->handleUniquesAndMythics->replacedSpecialItem()) {
                    dump($this->inventorySlots->pluck('item.affix_name')->toArray());
                    continue;
                }

                $slotForReplacedSpecialItem = $this->fetchBestItemForPositionFromInventory->fetchBestItemForPosition(
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

            if (in_array($position, [EquippablePositions::TRINKET, EquippablePositions::ARTIFACT])) {
                $character = $this->handleHands->setCurrentlyEquipped($this->currentlyEquippedSlots)
                                               ->handleHands($character, $bestSlot, $position);


                $this->fetchInventoryDetails($character);

                continue;
            }

            $this->handleRegularComparisonAndReplace->setCurrentlyEquipped($this->currentlyEquippedSlots)
                                                    ->handleRegularComparison($character, $bestSlot, $position);

            $this->fetchInventoryDetails($character);
        }
    }

    protected function fetchInventoryDetails(Character $character): void {

        $oldCurrentlyEquipped         = $this->currentlyEquippedSlots;

        $this->currentlyEquippedSlots = $this->fetchEquipped($character);

        $this->inventorySlots         = $character->inventory
            ->slots()
            ->where('equipped', false)
            ->whereHas('item', function($query) {
                $query->whereNotIn('type', ['quest', 'alchemy']);
            })
            ->get();

        if (!is_null($oldCurrentlyEquipped)) {
            $this->equipmentHasChanged = $this->currentlyEquippedSlots->diff($oldCurrentlyEquipped)->count() > 0;
        }

        $this->fetchBestItemForPositionFromInventory = $this->fetchBestItemForPositionFromInventory->setInventory($this->inventorySlots);
    }
}
