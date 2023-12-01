<?php

namespace App\Game\CharacterInventory\Services;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\WeaponTypes;
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

        $this->fetchBestItemForPositionFromInventory = $this->fetchBestItemForPositionFromInventory
            ->setCurrentlyEquipped($this->currentlyEquippedSlots)
            ->setInventory($this->inventorySlots);

        $positions = EquippablePositions::equippablePositions();

        $character = $this->handleEquippingBest($character, $positions);

        $currentHands = collect($this->currentlyEquippedSlots->whereIn('position', [
            EquippablePositions::LEFT_HAND, EquippablePositions::RIGHT_HAND
        ])->all());

        if ($currentHands->count() === 1) {

            $hasSpecialEquipped = $this->currentlyEquippedSlots->filter(function($slot) {
                return $slot->item->is_unique || $slot->item->is_mythic;
            })->isNotEmpty();

            $currentSlot = $currentHands->first();

            $oppositeHand = EquippablePositions::getOppisitePosition($currentSlot->position);

            $typesForPosition = EquippablePositions::typesForPositions($oppositeHand);

            if ($currentSlot->item->type === WeaponTypes::WEAPON) {
                $typesForPosition = [ArmourTypes::SHIELD];
            }

            if ($currentSlot->item->type === ArmourTypes::SHIELD) {
                $index = array_search(ArmourTypes::SHIELD, $typesForPosition);

                if ($index !== false) {
                    unset($typesForPosition[$index]);
                }
            }

            $bestSlot = $this->fetchBestItemForPositionFromInventory
                ->fetchBestItemForPosition($typesForPosition, $hasSpecialEquipped);

            if (!is_null($bestSlot)) {

                $this->handleHands->setCurrentlyEquipped($this->currentlyEquippedSlots)
                    ->handleHands($character, $bestSlot, $oppositeHand);
            }
        }

    }

    protected function handleEquippingBest(Character $character, array $positions, array $overrideTypesForPosition = []): Character {
        foreach ($positions as $position) {

            $typesForEquip = EquippablePositions::typesForPositions($position);

            if (!empty($overrideTypesForPosition)) {
                $typesForEquip = $overrideTypesForPosition;
            }

            $bestSlot = $this->fetchBestItemForPositionFromInventory
                ->fetchBestItemForPosition($typesForEquip);


            if (is_null($bestSlot)) {

                continue;
            }

            if ($bestSlot->item->is_mythic || $bestSlot->item->is_unique) {
                $character = $this->handleUniquesAndMythics->setCurrentlyEquipped($this->currentlyEquippedSlots)
                    ->handleUniquesOrMythics($character, $position, $bestSlot);

                $this->fetchInventoryDetails($character);

                if (!$this->handleUniquesAndMythics->replacedSpecialItem()) {
                    continue;
                }

                $position = $this->handleUniquesAndMythics->getSpecialSlotPosition();

                $slotForReplacedSpecialItem = $this->fetchBestItemForPositionFromInventory
                    ->fetchBestItemForPosition(
                        EquippablePositions::typesForPositions($position),
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

        return $character->refresh();
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

        $this->fetchBestItemForPositionFromInventory = $this->fetchBestItemForPositionFromInventory
            ->setCurrentlyEquipped($this->currentlyEquippedSlots)
            ->setInventory($this->inventorySlots);
    }
}
