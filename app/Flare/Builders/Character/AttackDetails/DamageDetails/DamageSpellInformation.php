<?php

namespace App\Flare\Builders\Character\AttackDetails\DamageDetails;

use App\Flare\Builders\Character\BaseCharacterInfo;
use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;

class DamageSpellInformation {

    use FetchEquipped;

    /**
     * @var BaseCharacterInfo $baseCharacterInfo
     */
    private $baseCharacterInfo;

    private $classBonuses;

    public function __construct(ClassBonuses $classBonuses) {
        $this->classBonuses = $classBonuses;
    }

    /**
     * @param BaseCharacterInfo $baseCharacterInfo
     * @return void
     */
    public function setCharacterInformation(BaseCharacterInfo $baseCharacterInfo): DamageSpellInformation {
        $this->baseCharacterInfo = $baseCharacterInfo;

        return $this;
    }

    /**
     * Get characters spell damage.
     *
     * @param Character $character
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function getSpellDamage(Character $character, bool $voided = false): int {
        $damage = 0;

        $bonus = $this->classBonuses->hereticSpellDamageBonus($character);

        $slots = $this->fetchEquipped($character);

        if (is_null($slots)) {
            return 0;
        }

        foreach ($slots as $slot) {
            if ($slot->item->type === 'spell-damage') {
                if (!$voided) {
                    $damage += $slot->item->getTotalDamage();
                } else {
                    $damage += $slot->item->base_damage;
                }
            }
        }

        $damage = $this->calculateClassSpellDamage($character, $damage, $voided);

        return (int) ($damage + $damage * $bonus);
    }

    /**
     * Calculate the classes total spell damage.
     *
     * @param Character $character
     * @param int|float $damage
     * @param bool $voided
     * @return float|int
     * @throws \Exception
     */
    public function calculateClassSpellDamage(Character $character, int|float $damage, bool $voided = false): float|int {
        $classType = $character->classType();
        if ($damage === 0) {
            if ($classType->isHeretic()) {
                if (!$voided) {
                    $damage = $this->baseCharacterInfo->statMod($character,'int') * 0.2;
                } else {
                    $damage += $character->int * 0.02;
                }
            }
        }

        if ($classType->isHeretic() || $classType->isArcaneAlchemist()) {
            if ($voided) {
                $damage += $character->int * 0.30;
            } else {
                $damage += $this->baseCharacterInfo->statMod($character,'int') * 0.30;
            }
        }

        if ($classType->isProphet()) {
            if ($this->classBonuses->prophetHasDamageSpells($character)) {
                if ($voided) {
                    $damage += $character->chr * 0.15;
                } else {
                    $damage += $this->baseCharacterInfo->statMod($character,'int') * 0.15;
                }
            }
        }

        return $damage;
    }
}
