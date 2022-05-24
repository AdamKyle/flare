<?php

namespace App\Flare\Builders\Character\AttackDetails;

use App\Flare\Builders\Character\AttackDetails\DamageDetails\DamageSpellInformation;
use App\Flare\Builders\Character\AttackDetails\DamageDetails\WeaponInformation;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use Illuminate\Support\Collection;

class CharacterDamageInformation {

    use FetchEquipped;

    private $weaponInformation;

    private $damageSpellInformation;

    public function __construct(WeaponInformation $weaponInformation, DamageSpellInformation $damageSpellInformation) {
        $this->weaponInformation      = $weaponInformation;
        $this->damageSpellInformation = $damageSpellInformation;
    }

    /**
     *
     * @param Character $character
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function getWeaponDamage(Character $character, bool $voided = false): int {
        return $this->weaponInformation->setCharacterInformation($character->getInformation()->getBaseCharacterInfo())->getWeaponDamage($character, $voided);
    }

    /**
     * Returns an instance of the Weapon Information.
     *
     * @return WeaponInformation
     */
    public function getWeaponInformation(): WeaponInformation {
        return $this->weaponInformation;
    }

    /**
     * Returns an instance of the damage spell info.
     *
     * @return DamageSpellInformation
     */
    public function getDamageSpellInformation(): DamageSpellInformation {
        return $this->damageSpellInformation;
    }

    /**
     * Get the spell damage for a character.
     *
     * @param Character $character
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function getSpellDamage(Character $character, bool $voided = false): int {
        return $this->damageSpellInformation->setCharacterInformation($character->getInformation()->getBaseCharacterInfo())->getSpellDamage($character, $voided);
    }

    /**
     * Get the characters ring damage.
     *
     * @param bool $voided
     * @return int
     */
    public function getRingDamage(Character $character, bool $voided = false): int {
        $damage = [];

        foreach ($this->fetchInventory($character) as $slot) {
            if ($slot->item->type === 'ring') {
                if (!$voided) {
                    $damage[] = $slot->item->getTotalDamage();
                } else {
                    $damage[] = $slot->item->base_damage;
                }
            }
        }

        if (!empty($damage)) {
            return max($damage);
        }

        return 0;
    }

    /**
     * Fetch the inventory for the character with equipped items.
     *
     * @return Collection
     */
    protected function fetchInventory(Character $character): Collection
    {
        $slots = $this->fetchEquipped($character);

        if (is_null($slots)) {
            return collect([]);
        }

        return $slots;
    }
}
