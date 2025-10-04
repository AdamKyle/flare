<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class Affixes extends BattleBase
{
    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct($characterCacheData);
    }

    public function getCharacterAffixDamage(array $attackData, float $monsterResistance = 0.0): int
    {

        if ($attackData['attack_type'] === AttackTypeValue::DEFEND) {
            return 0;
        }

        $attribute = isset($attackData['weapon_damage']) ? 'weapon_damage' : 'spell_damage';

        $totalDamage = $attackData['affixes']['stacking_damage'] - $attackData['damage_deduction'];
        $nonStackingDamage = ($attackData['affixes']['non_stacking_damage'] - $attackData['damage_deduction']) + $totalDamage;
        $cantBeResisted = $attackData['affixes']['cant_be_resisted'];

        $weaponDamage = $attackData[$attribute] + ($attackData[$attribute] * $totalDamage);
        $nonStackingWeaponDamage = $attackData[$attribute] + ($attackData[$attribute] * $nonStackingDamage);

        if ($this->isRaidBoss && $weaponDamage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $weaponDamage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        if ($totalDamage > 0 || $nonStackingDamage > 0) {

            if ($cantBeResisted) {
                $this->addMessage('The enemy cannot resist your enchantments! They are so glowy!', 'regular');

                $this->addMessage('Your enchantments glow with rage. Your enemy cowers. (non resisted dmg): '.number_format($weaponDamage), 'player-action');

                return $weaponDamage;
            } else {

                if ($nonStackingWeaponDamage > 0) {
                    return $this->doAffixDamage($nonStackingWeaponDamage, $weaponDamage, $monsterResistance);
                } else {
                    $this->addMessage('Your (non resistible) enchantments glow with rage. Your enemy cowers: '.number_format($weaponDamage), 'player-action');

                    return $weaponDamage;
                }
            }
        }

        return 0;
    }

    public function getAffixLifeSteal(Character $character, array $attackData, int $monsterHealth, float $resistance = 0.0): int
    {
        if (! $monsterHealth > 0) {
            return 0;
        }

        $affixLifeStealing = $attackData['affixes'][$character->classType()->isVampire() ? 'stacking_life_stealing' : 'life_stealing'] - $attackData['damage_deduction'];
        $cantBeResisted = $attackData['affixes']['cant_be_resisted'];

        if (is_null($affixLifeStealing)) {
            return 0;
        }

        if ($affixLifeStealing <= 0) {
            return 0;
        }

        if (! $character->classType()->isVampire()) {
            $this->addMessage('One of your life stealing enchantments causes the enemy to fall to their knees in agony!', 'player-action');
        } else {
            $this->addMessage('The enemy screams in pain as you siphon large amounts of their health towards you!', 'player-action');
        }

        $damage = $monsterHealth * $affixLifeStealing;

        if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        if ($cantBeResisted || $this->isEnemyEntranced) {

            $this->addMessage('The enemy\'s blood flows through the air and gives you life: '.number_format($damage), 'player-action');

            return $damage;
        }

        $dc = 50 + 50 * $resistance;
        $roll = rand(1, 100);

        if ($roll < $dc) {
            $this->addMessage('The enemy resists your attempt to steal it\'s life.', 'enemy-action');
        } else {
            $this->addMessage('The enemy\'s blood flows through the air and gives you life: '.number_format($damage), 'player-action');

            return $damage;
        }

        return 0;
    }

    protected function doAffixDamage(int $totalDamage, int $nonStackingDamage, float $resistance = 0.0)
    {
        $dc = 100 - 100 * $resistance;

        if ($dc <= 0 || rand(1, 100) > $dc) {
            $this->addMessage('Your damaging enchantments (resistible) have been resisted. However ...', 'enemy-action');

            $this->addMessage('Your (non resistible) enchantments glow with rage. Your enemy cowers: '.number_format($totalDamage), 'player-action');

            return $totalDamage;
        } else {

            $this->addMessage('Your enchantments glow with rage. Your enemy cowers: '.number_format($nonStackingDamage), 'player-action');

            return $nonStackingDamage;
        }
    }
}
