<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Monster\ServerMonster;

class Affixes extends BattleBase {

    private CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData) {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
    }

    public function getCharacterAffixDamage(Character $character, ServerMonster $monster, array $attackData): int {
        $totalDamage       = $attackData['affixes']['stacking_damage'] - $attackData['affixes']['stacking_damage'] * $attackData['damage_deduction'];
        $nonStackingDamage = $attackData['affixes']['non_stacking_damage'] - $attackData['affixes']['stacking_damage'] * $attackData['damage_deduction'];
        $cantBeResisted    = $attackData['affixes']['cant_be_resisted'];

        if ($totalDamage > 0 || $nonStackingDamage > 0) {
            if ($cantBeResisted) {
                $this->addMessage('The enemy cannot resist your enchantments! They are so glowy!', 'regular');

                $this->addMessage('Your enchantments glow with rage. Your enemy cowers. (non resisted dmg): ' . number_format($totalDamage + $nonStackingDamage));

                return $totalDamage + $nonStackingDamage;
            } else {
                $dc = 100 - 100 * $monster->getMonsterStat('affix_resistance');

                if ($dc <= 0 || rand(1, 100) > $dc) {
                    $this->addMessage('Your damaging enchantments (resistible) have been resisted.', 'enemy-action');

                }  else {

                    $this->addMessage('Your enchantments glow with rage. Your enemy cowers: ' . number_format($nonStackingDamage), 'player-action');

                    return $nonStackingDamage;
                }
            }
        }

        return 0;
    }

    public function getAffixLifeSteal(Character $character, ServerMonster $monster, array $attackData): int {

        $affixLifeStealing = $attackData['affixes'][$character->classType()->isVampire() ? 'stacking_life_stealing' : 'life_stealing'] * $attackData['damage_deduction'];
        $cantBeResisted    = $attackData['affixes']['cant_be_resisted'];

        if ($affixLifeStealing <= 0) {
            return 0;
        }

        if (!$character->classType()->isVampire()) {
            $this->addMessage('One of your life stealing enchantments causes the enemy to fall to their knees in agony!', 'player-action');
        } else {
            $this->addMessage('The enemy screams in pain as you siphon large amounts of their health towards you!', 'player-action');
        }

        if ($cantBeResisted) {

            $this->addMessage('The enemy\'s blood flows through the air and gives you life: ' + number_format($affixLifeStealing), 'player-action');

            return $affixLifeStealing;
        }

        $dc = 100 - 100 * $monster->getMonsterStat('affix_resistance');

        if ($dc <= 0 || rand(1, 100) > $dc) {
            $this->addMessage('The enemy resists your attempt to steal it\'s life.', 'enemy-action');
        } else {
            $this->addMessage('The enemy\'s blood flows through the air and gives you life: ' + number_format($affixLifeStealing), 'player-action');

            return $affixLifeStealing;
        }

        return 0;
    }
}
