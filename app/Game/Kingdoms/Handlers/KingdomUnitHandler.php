<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;

class KingdomUnitHandler extends BaseDefenderHandler
{
    private array $defenderUnits = [];

    private DefenderSiegeHandler $defenderSiegeHandler;

    private DefenderArcherHandler $defenderArcherHandler;

    public function __construct(DefenderSiegeHandler $defenderSiegeHandler, DefenderArcherHandler $defenderArcherHandler)
    {
        $this->defenderSiegeHandler = $defenderSiegeHandler;
        $this->defenderArcherHandler = $defenderArcherHandler;
    }

    /**
     * Get the defender Units.
     */
    public function getDefenderUnits(): array
    {
        return $this->defenderUnits;
    }

    /**
     * Units attack units.
     *
     * - Defender siege and archers attack first.
     * - Attackers attack next.
     */
    public function attackUnits(Kingdom $kingdom, int $attackingKingdomId): array
    {
        $attackingUnits = $this->defenderAttacksAttackerUnits($kingdom);

        $this->setAttackingUnits($attackingUnits);

        $this->unitOnUnitAttack($kingdom, $attackingKingdomId);

        return $this->getAttackingUnits();
    }

    /**
     * Unit on unit attack.
     *
     * Damage is done based on the defender defence and the attackers attack.
     * Damages both Defender and attacker.
     */
    protected function unitOnUnitAttack(Kingdom $kingdom, int $attackingKingdomId): void
    {
        $defence = $this->getDefenderDefence($kingdom);
        $attack = $this->getAttackerAttack($attackingKingdomId);

        if ($defence <= 0 || $attack <= 0) {
            return;
        }

        if ($attack > $defence) {
            $defenderDamage = $defence / $attack;
        } else {
            $defenderDamage = $attack / $defence;
        }

        if ($defenderDamage > 1) {
            $defenderDamage = 1;
        }

        $this->updateDefenderUnits($kingdom, $defenderDamage);

        $attackerDamage = $defence / $attack;

        if ($attackerDamage > 1) {
            $attackerDamage = 1;
        }

        $this->updateAttackingUnits($attackerDamage, true);
    }

    /**
     * The defender attacks the attacker.
     */
    protected function defenderAttacksAttackerUnits(Kingdom $kingdom): array
    {
        $defenderSiegeHandler = $this->defenderSiegeHandler->setAttackingUnits($this->attackingUnits);

        $defenderSiegeHandler->attackUnitsWithSiegeWeapons($kingdom);

        $attackingUnits = $defenderSiegeHandler->getAttackingUnits();

        $defendingArcherHandler = $this->defenderArcherHandler->setAttackingUnits($attackingUnits);

        $defendingArcherHandler->attackUnitsWithArcherUnits($kingdom);

        return $defendingArcherHandler->getAttackingUnits();
    }

    /**
     * Get the defender defence.
     */
    protected function getDefenderDefence(Kingdom $kingdom): int
    {
        $defence = 0;

        foreach ($kingdom->units as $unit) {
            if (! $unit->gameUnit->siege_weapon && ! $unit->gameUnit->is_settler) {
                $defence += $unit->amount * $unit->gameUnit->defence;
            }
        }

        return $defence;
    }

    /**
     * Get the attackers attack.
     */
    protected function getAttackerAttack(int $attackingKingdomId): int
    {
        $attack = 0;

        foreach ($this->attackingUnits as $unitData) {
            $unit = KingdomUnit::where('id', $unitData['unit_id'])
                ->where('kingdom_id', $attackingKingdomId)
                ->first();

            if ($unit->gameUnit->is_settler) {
                continue;
            }

            $attack += $unitData['amount'] * $unit->gameUnit->attack;
        }

        return $attack;
    }

    /**
     * Update the defender units.
     *
     * @return void
     */
    protected function updateDefenderUnits(Kingdom $kingdom, float $damage)
    {
        foreach ($kingdom->units as $unit) {
            if ($unit->gameUnit->siege_weapon) {
                continue;
            }

            $newAmount = $unit->amount - ($unit->amount * $damage);

            $unit->update([
                'amount' => $newAmount > 0 ? $newAmount : 0,
            ]);

            $unit = $unit->refresh();

            $this->defenderUnits[] = [
                'unit_id' => $unit->id,
                'name' => $unit->gameUnit->name,
                'amount' => $unit->amount,
            ];
        }
    }
}
