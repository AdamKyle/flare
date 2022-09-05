<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Traits\CalculateMorale;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class AttackWithItemsService {

    use ResponseBuilder, CalculateMorale;

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @var array $oldBuildings
     */
    private array $oldBuildings = [];

    /**
     * @var array $newBuildings
     */
    private array $newBuildings = [];

    /**
     * @var array $oldUnits
     */
    private array $oldUnits = [];

    /**
     * @var array $newUnits
     */
    private array $newUnits = [];

    /**
     * @param UpdateKingdom $updateKingdom
     */
    public function __construct(UpdateKingdom $updateKingdom) {
        $this->updateKingdom = $updateKingdom;
    }

    /**
     * Use items on a kingdom.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $slots
     * @return array
     */
    public function useItemsOnKingdom(Character $character, Kingdom $kingdom, array $slots): array {

       if (!$this->doesCharacterHaveItems($character->inventory, $slots)) {
           return $this->errorResult('You are not allowed to do that.');
       }

       $this->setOldBuildings($kingdom);
       $this->setOldUnits($kingdom);

       $damage    = $this->gatherDamage($character->inventory, $slots);
       $reduction = $this->getReductionToDamage($kingdom);

       $damage -= ($damage * $reduction);

       $currentMorale = $kingdom->current_morale;

       $kingdom = $this->damageBuildings($kingdom, ($damage / 2));
       $kingdom = $this->damageUnits($kingdom, ($damage / 2));


       $newMorale  = $this->calculateNewMorale($kingdom->refresh(), $currentMorale);

       $kingdom->update(['current_morale' => $newMorale]);

       $kingdom = $kingdom->refresh();

       if ($newMorale <= 0.0) {
           $moraleLoss = 1.0;
       } else {
           $moraleLoss = $currentMorale - $newMorale;
       }

       event(new GlobalMessageEvent($character->name . ' has done devastating damage to the kingdom: ' .
           $kingdom->name . ' on the plane: ' . $kingdom->gameMap->name . ' At (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position .
           ' doing a total of: ' . number_format($damage * 100) . '% damage.'
       ));

       $this->createLogs($character, $kingdom, $damage, $moraleLoss);

       $character->inventory->slots()->whereIn('id', $slots)->delete();

       return $this->successResult([
           'message' => 'Dropped items on kingdom!'
       ]);
    }


    /**
     * Create the logs for both defender and attacker.
     *
     * - If the defender is not an NPC kingdom we create the log for them.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param float $damageDone
     * @param float $moraleLoss
     * @return void
     */
    protected function createLogs(Character $character, Kingdom $kingdom, float $damageDone, float $moraleLoss): void {
        $attributes = [
            'to_kingdom_id'  => $kingdom->id,
            'status'         => KingdomLogStatusValue::BOMBS_DROPPED,
            'old_buildings'  => $this->oldBuildings,
            'new_buildings'  => $this->newBuildings,
            'old_units'      => $this->oldUnits,
            'new_units'      => $this->newUnits,
            'item_damage'    => $damageDone,
            'morale_loss'    => $moraleLoss,
            'published'      => true,
        ];

        if (!$kingdom->npc_owned) {
            $attributes['character_id']           = $kingdom->character_id;
            $attributes['attacking_character_id'] = $character->id;

            KingdomLog::create($attributes);

            event(new ServerMessageEvent($character->user, $character->name . ' has dropped bombs on your kingdom: ' .
                $kingdom->name . ' on the plane: ' . $kingdom->gameMap->name . ' At (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position .
                ' doing a total of: ' . number_format($damageDone * 100) . '% damage.'));

            $this->updateKingdom->updateKingdomLogs($kingdom->refresh()->character);
        }

        $attributes['character_id'] = $character->id;

        KingdomLog::create($attributes);

        event(new ServerMessageEvent($character->user, 'You have dropped bombs on a kingdom: ' .
            $kingdom->name . ' on the plane: ' . $kingdom->gameMap->name . ' At (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position .
            ' doing a total of: ' . number_format($damageDone * 100) . '% damage.'));

        $this->updateKingdom->updateKingdomLogs($character->refresh());
    }

    /**
     * Validate that the character has the items selected.
     *
     * @param Inventory $inventory
     * @param array $slots
     * @return bool
     */
    protected function doesCharacterHaveItems(Inventory $inventory, array $slots): bool {
       $missingItems = [];

       foreach ($slots as $slotId) {
           if (is_null($inventory->slots->where('id', $slotId)->first())) {
               array_push($missingItems, $slotId);
           }
       }

       return count($missingItems) > 0 ? false : true;
    }

    /**
     * Damage the buildings.
     *
     * @param Kingdom $kingdom
     * @param float $damage
     * @return Kingdom
     */
    protected function damageBuildings(Kingdom $kingdom, float $damage): Kingdom {
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
                'name'       => $building->name,
                'durability' => $building->current_durability,
            ];
        }

        return $kingdom->refresh();
    }

    /**
     * Damage the units.
     *
     * @param Kingdom $kingdom
     * @param float $damage
     * @return Kingdom
     */
    protected function damageUnits(Kingdom $kingdom, float $damage): Kingdom {
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
                'name'   => $unit->gameUnit->name,
                'amount' => $unit->amount,
            ];
        }

        return $kingdom->refresh();
    }

    /**
     * Set the old building data.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    protected function setOldBuildings(Kingdom $kingdom): void {
        foreach ($kingdom->buildings as $building) {
            $this->oldBuildings[] = [
                'name'       => $building->name,
                'durability' => $building->current_durability,
            ];
        }
    }

    /**
     * set the old unit data.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    protected function setOldUnits(Kingdom $kingdom): void {
        foreach ($kingdom->units as $unit) {
            $this->oldUnits[] = [
                'name'   => $unit->gameUnit->name,
                'amount' => $unit->amount,
            ];
        }
    }

    /**
     * Gathers item damage from selected items.
     *
     * @param Inventory $inventory
     * @param $slots
     * @return float
     */
    protected function gatherDamage(Inventory $inventory, $slots): float {
       $damage = 0.0;

       foreach ($slots as $slotId) {
           $slot = $inventory->slots->where('id', $slotId)->first();

           $damage += $slot->item->kingdom_damage;
       }

       return $damage;
    }

    /**
     * get the reduction in damage.
     *
     * @param Kingdom $kingdom
     * @return float
     */
    protected function getReductionToDamage(Kingdom $kingdom): float {
       $totalDefence = $kingdom->fetchKingdomDefenceBonus();
       $reduction    = 0.0;

       if ($totalDefence > 1) {
           $reduction = ($totalDefence - 1) / 0.05;

           if ($reduction < 0.05) {
               return 0.05;
           }
       }

       return $reduction;
    }
}
