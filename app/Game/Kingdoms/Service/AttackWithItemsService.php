<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Traits\CalculateMorale;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Collection;

class AttackWithItemsService
{
    use CalculateMorale, ResponseBuilder;

    private UpdateKingdom $updateKingdom;

    private array $oldBuildings = [];

    private array $newBuildings = [];

    private array $oldUnits = [];

    private array $newUnits = [];

    public function __construct(UpdateKingdom $updateKingdom)
    {
        $this->updateKingdom = $updateKingdom;
    }

    /**
     * Use items on a kingdom.
     */
    public function useItemsOnKingdom(Character $character, Kingdom $kingdom, array $slots): array
    {

        $alchemySlots = $this->getValidatedAlchemySlots($character, $slots);

        if (is_null($alchemySlots)) {
            return $this->errorResult('You don\'t own these items.');
        }

        if ($character->id === $kingdom->character_id) {
            return $this->errorResult('You cannot attack your own kingdoms.');
        }

        if (! is_null($kingdom->protected_until)) {
            return $this->errorResult('This kingdom is currently under The Creators protection and cannot be targeted right now.');
        }

        if ($character->map->game_map_id !== $kingdom->game_map_id) {
            return $this->errorResult('You need to be on the same plane as the kingdom you want to attack with items.');
        }

        $itemResistance = $kingdom->kingdomItemResistanceBonus();

        $this->setOldBuildings($kingdom);
        $this->setOldUnits($kingdom);

        $rawDamage = $this->gatherDamage($alchemySlots, $slots);
        $kingdomDefence = $kingdom->fetchKingdomDefenceBonus();
        $damageAfterDefence = max(0.0, $rawDamage - $kingdomDefence);
        $damage = $damageAfterDefence;

        if ($itemResistance > 0) {
            $damage -= ($damage * $itemResistance);
        }

        $damage = max(0.0, $damage);
        $buildingDamage = $damage / 2;
        $unitDamage = $damage / 2;

        $currentMorale = $kingdom->current_morale;

        $kingdom = $this->damageBuildings($kingdom, $buildingDamage);
        $kingdom = $this->damageUnits($kingdom, $unitDamage);

        $newMorale = $this->calculateNewMorale($kingdom->refresh(), $currentMorale);

        $kingdom->update(['current_morale' => $newMorale]);

        $kingdom = $kingdom->refresh();

        if ($newMorale <= 0.0) {
            $moraleLoss = 1.0;
        } else {
            $moraleLoss = $currentMorale - $newMorale;
        }

        event(new GlobalMessageEvent(
            $character->name . ' has done devastating damage to the kingdom: ' .
                $kingdom->name . ' on the plane: ' . $kingdom->gameMap->name . ' At (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position .
                ' doing a total of: ' . number_format($damage * 100) . '% damage.'
        ));

        $this->createLogs($character, $kingdom, $damage, $moraleLoss, [
            'raw_item_damage' => $rawDamage,
            'kingdom_defence' => $kingdomDefence,
            'damage_after_defence' => $damageAfterDefence,
            'item_resistance' => $itemResistance,
            'final_damage' => $damage,
            'building_damage' => $buildingDamage,
            'unit_damage' => $unitDamage,
        ]);

        foreach (array_count_values($slots) as $slotId => $amountUsed) {
            $slot = $alchemySlots->get($slotId);
            $remainingAmount = $slot->amount - $amountUsed;

            if ($remainingAmount === 0) {
                $slot->delete();
            } else {
                $slot->update(['amount' => $remainingAmount]);
            }
        }

        return $this->successResult([
            'message' => 'Dropped items on kingdom!',
        ]);
    }

    /**
     * Create the logs for both defender and attacker.
     *
     * - If the defender is not an NPC kingdom we create the log for them.
     */
    protected function createLogs(
        Character $character,
        Kingdom $kingdom,
        float $damageDone,
        float $moraleLoss,
        array $damageBreakdown,
    ): void
    {
        $attributes = [
            'to_kingdom_id' => $kingdom->id,
            'status' => KingdomLogStatusValue::BOMBS_DROPPED,
            'old_buildings' => $this->oldBuildings,
            'new_buildings' => $this->newBuildings,
            'old_units' => $this->oldUnits,
            'new_units' => $this->newUnits,
            'item_damage' => $damageDone,
            'morale_loss' => $moraleLoss,
            'additional_details' => [
                'item_damage_breakdown' => $damageBreakdown,
            ],
            'published' => true,
        ];

        if (! $kingdom->npc_owned) {
            $attributes['character_id'] = $kingdom->character_id;
            $attributes['attacking_character_id'] = $character->id;

            KingdomLog::create($attributes);

            event(new ServerMessageEvent($kingdom->character->user, $character->name . ' has dropped bombs on your kingdom: ' .
                $kingdom->name . ' on the plane: ' . $kingdom->gameMap->name . ' At (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position .
                ' doing a total of: ' . number_format($damageDone * 100) . '% damage.'));

            $kingdom = $kingdom->refresh();

            $this->updateKingdom->updateKingdomLogs($kingdom->character, true);
            $this->updateKingdom->updateKingdom($kingdom);
        }

        $attributes['character_id'] = $character->id;

        KingdomLog::create($attributes);

        event(new ServerMessageEvent($character->user, 'You have dropped bombs on a kingdom: ' .
            $kingdom->name . ' on the plane: ' . $kingdom->gameMap->name . ' At (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position .
            ' doing a total of: ' . number_format($damageDone * 100) . '% damage.'));

        $this->updateKingdom->updateKingdomLogs($character->refresh(), true);
    }

    /**
     * Validate that the character has the items selected.
     */
    protected function getValidatedAlchemySlots(Character $character, array $slots): ?Collection
    {
        if (is_null($character->alchemyBag) || count($slots) === 0) {
            return null;
        }

        $uniqueSlotIds = array_unique($slots);
        $alchemySlots = $character->alchemyBag->slots()
            ->where('character_id', $character->id)
            ->whereIn('id', $uniqueSlotIds)
            ->whereHas('item', function ($query) {
                $query->where('damages_kingdoms', true);
            })
            ->with('item')
            ->get()
            ->keyBy('id');

        if ($alchemySlots->count() !== count($uniqueSlotIds)) {
            return null;
        }

        foreach (array_count_values($slots) as $slotId => $selectedAmount) {
            if ($selectedAmount > $alchemySlots->get($slotId)->amount) {
                return null;
            }
        }

        return $alchemySlots;
    }

    /**
     * Damage the buildings.
     */
    protected function damageBuildings(Kingdom $kingdom, float $damage): Kingdom
    {
        foreach ($kingdom->buildings as $building) {
            $newDurability = $building->current_durability - ($building->current_durability * $damage);

            if ($newDurability < 0) {
                $newDurability = 0;
            }

            $building->update([
                'current_durability' => $newDurability,
            ]);

            $building = $building->refresh();

            $this->newBuildings[] = [
                'name' => $building->name,
                'durability' => $building->current_durability,
            ];
        }

        return $kingdom->refresh();
    }

    /**
     * Damage the units.
     */
    protected function damageUnits(Kingdom $kingdom, float $damage): Kingdom
    {
        foreach ($kingdom->units as $unit) {
            $newAmount = $unit->amount - ($unit->amount * $damage);

            if ($newAmount < 0) {
                $newAmount = 0;
            }

            $unit->update([
                'amount' => $newAmount,
            ]);

            $unit = $unit->refresh();

            $this->newUnits[] = [
                'name' => $unit->gameUnit->name,
                'amount' => $unit->amount,
            ];
        }

        return $kingdom->refresh();
    }

    /**
     * Set the old building data.
     */
    protected function setOldBuildings(Kingdom $kingdom): void
    {
        foreach ($kingdom->buildings as $building) {
            $this->oldBuildings[] = [
                'name' => $building->name,
                'durability' => $building->current_durability,
            ];
        }
    }

    /**
     * set the old unit data.
     */
    protected function setOldUnits(Kingdom $kingdom): void
    {
        foreach ($kingdom->units as $unit) {
            $this->oldUnits[] = [
                'name' => $unit->gameUnit->name,
                'amount' => $unit->amount,
            ];
        }
    }

    /**
     * Gathers item damage from selected items.
     */
    protected function gatherDamage(Collection $alchemySlots, array $slots): float
    {
        $damage = 0.0;

        foreach ($slots as $slotId) {
            $slot = $alchemySlots->get($slotId);

            $damage += $slot->item->kingdom_damage;
        }

        return $damage;
    }
}
