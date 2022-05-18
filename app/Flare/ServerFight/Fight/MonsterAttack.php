<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Monster\ServerMonster;

class MonsterAttack extends BattleBase {

    private int $characterHealth;

    private int $monsterHealth;

    private bool $isVoided;

    private CharacterCacheData $characterCacheData;

    private Entrance $entrance;

    private CanHit $canHit;


    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit) {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
    }

    public function setCharacterHealth(int $characterHealth): MonsterAttack {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): MonsterAttack {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function setIsCharacterVoided(bool $isVoided): MonsterAttack {
        $this->isVoided = $isVoided;

        return $this;
    }

    public function getCharacterHealth() {
        return $this->characterHealth;
    }

    public function getMonsterHealth() {
        return $this->monsterHealth;
    }

    public function monsterAttack(ServerMonster $monster, Character $character, string $previousAttackType) {
        $this->attackPlayer($monster, $character);
        $this->fireEnchantments($monster, $character);
        $this->castSpells($monster, $character, $previousAttackType);
    }

    protected function attackPlayer(ServerMonster $monster, Character $character) {
        $attack = $monster->buildAttack();

        if (rand(1, 100) > (100 - 100 * $monster->getMonsterStat('criticality'))) {
            $this->addMessage($monster->getName() . ' grows enraged and lashes out with all fury! (Critical Strike!)', 'regular');

            $attack *= 2;
        }

        $this->characterHealth -= $attack;

        $this->addMessage($monster->getName() . ' hits for: ' + number_format($attack), 'enemy-action');
    }

    protected function fireEnchantments(ServerMonster $monster, Character $character) {
        $maxAffixDamage  = $monster->getMonsterStat('max_affix_damage');
        $maxAffixDamage  = rand(1, $maxAffixDamage);
        $damageReduction =  $this->characterCacheData->getCachedCharacterData($character, 'affix_damage_reduction');

        $maxAffixDamage = $maxAffixDamage - $maxAffixDamage * $damageReduction;

        if ($damageReduction > 0.0) {
            $this->addMessage('Your rings negate some of the enemy\'s enchantment damage.', 'player-action');
        }

        if ($maxAffixDamage > 0) {
            $this->characterHealth -= $maxAffixDamage;

            $this->addMessage($monster->getName() . '\'s enchantments glow, lashing out for: ' + number_format($maxAffixDamage), 'enemy-action');
        }
    }

    protected function castSpells(ServerMonster $monster, Character $character, string $previousAttackType) {
        if (!$this->canHit->canMonsterCastSpell($character, $monster, $this->isVoided)) {
            $this->addMessage($monster->name() . '\'s Spells fizzle and fail to fire.', 'regular');

            return;
        }

        $spellDamage = $monster->getMonsterStat('spell_damage');


        if ($spellDamage > 0 )  {
            $spellEvasion = $this->characterCacheData->getCachedCharacterData('spell_evasion');
            $dc           = 100 - 100 * $spellEvasion;
            $roll         = rand(1, 100);

            if ($spellEvasion >= 1 || $roll > $dc) {
                $this->addMessage('You evade the enemy\'s spells!', 'player-action');

                return;
            }

             $criticality = $monster->getMonsterStat('criticality');

            if (rand(1, 100) > (100 - 100 * $criticality)) {
                $this->addMessage($monster->getName() . ' With a fury of hatred their spells fly viciously at you! (Critical Strike!)', 'regular');

                $spellDamage *= 2;
            }

            if ($previousAttackType === 'defend') {
                if ($this->characterCacheData->getCachedCharacterData($character, 'ac') >= $spellDamage) {
                    $this->addMessage('You managed to block the enemy\'s spells with your armour!', 'player-action');
                }
            }

            $this->characterHealth -= $spellDamage;

            $this->addMessage($monster->getName() . '\'s spells burst toward you doing: ' . number_format($spellDamage), 'enemy-action');
        }
    }
}
