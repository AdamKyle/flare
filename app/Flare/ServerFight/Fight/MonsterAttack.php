<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\CharacterAttacks\PlayerHealing;
use App\Flare\ServerFight\Monster\MonsterSpecialAttack;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class MonsterAttack extends BattleBase {

    private PlayerHealing $playerHealing;

    private Entrance $entrance;

    private CanHit $canHit;


    public function __construct(CharacterCacheData $characterCacheData, PlayerHealing $playerHealing, Entrance $entrance, CanHit $canHit) {
        parent::__construct($characterCacheData);

        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->playerHealing      = $playerHealing;
    }

    public function setIsCharacterVoided(bool $isVoided): MonsterAttack {
        $this->isVoided = $isVoided;

        return $this;
    }

    public function monsterAttack(ServerMonster $monster, Character $character, string $previousAttackType) {
        if ($this->canHit->canMonsterHitPlayer($character, $monster, $this->isVoided)) {
            $this->attackPlayer($monster, $character, $previousAttackType);

            $this->playerBattleHealing($character, $previousAttackType);

            $this->doPlayerCounterMonster($character, $monster);
        } else {
            $this->addMessage($monster->getName() . ' misses!', 'enemy-action');
        }

        if ($this->monsterHealth <= 0) {
            return;
        }

        if (!$this->isEnemyVoided) {
            $this->fireEnchantments($monster, $character);
            $this->playerBattleHealing($character, $previousAttackType);
            $this->castSpells($monster, $character, $previousAttackType);
            $this->playerBattleHealing($character, $previousAttackType);
        }

        $this->monsterElementalAttack($monster, $character);
        $this->playerBattleHealing($character, $previousAttackType);
        $this->monsterSpecialAttack($monster, $character);
        $this->playerBattleHealing($character, $previousAttackType);

        if ($this->characterHealth <= 0) {
            $this->playerResurrection($monster, $character, $previousAttackType);
        }
    }

    protected function monsterElementalAttack(ServerMonster $monster, Character $character) {
        if (
            $monster->getMonsterStat('is_raid_monster') ||
            $monster->getMonsterStat('is_raid_boss') ||
            $monster->canMonsterUseElementalAttack()
        ) {
            $elementalData = $this->characterCacheData->getCachedCharacterData($character, 'elemental_atonement');

            if (is_null($elementalData)) {
                return;
            }

            $elementalData = $elementalData['atonements'];

            $elementalAttack = resolve(ElementalAttack::class);

            $elementalAttack->setMonsterHealth($this->monsterHealth);
            $elementalAttack->setCharacterHealth($this->characterHealth);

            $elementalAttack->doElementalAttack($elementalData, $monster->getElementData(), $monster->buildAttack(), true);

            $this->characterHealth = $elementalAttack->getCharacterHealth();
            $this->monsterHealth   = $elementalAttack->getMonsterHealth();

            $this->mergeMessages($elementalAttack->getMessages());

            $elementalAttack->clearMessages();
        }
    }

    protected function monsterSpecialAttack(ServerMonster $monster, Character $character) {
        if (
            $monster->getMonsterStat('is_raid_monster') ||
            $monster->getMonsterStat('is_raid_boss') ||
            $monster->canMonsterUseElementalAttack()
        ) {
            $ac = $this->characterCacheData->getCachedCharacterData($character, 'ac');

            $monsterSpecialAttack = resolve(MonsterSpecialAttack::class);

            $monsterSpecialAttack->setMonsterHealth($this->monsterHealth);
            $monsterSpecialAttack->setCharacterHealth($this->characterHealth);

            $specialAttackType = $monster->getMonsterStat('raid_special_attack_type');
            $damageStatAmount  = $monster->getMonsterStat($monster->getMonsterStat('damage_stat'));

            if (!is_null($specialAttackType)) {
                $monsterSpecialAttack->doSpecialAttack($specialAttackType, $damageStatAmount, $ac);
            }

            $this->characterHealth = $monsterSpecialAttack->getCharacterHealth();
            $this->monsterHealth   = $monsterSpecialAttack->getMonsterHealth();

            $this->mergeMessages($monsterSpecialAttack->getMessages());

            $monsterSpecialAttack->clearMessages();
        }
    }

    protected function playerResurrection(Character $character, string $previousAttackType) {
        $previousAttackType = $this->characterCacheData->getDataFromAttackCache($character, $previousAttackType);

        $this->playerHealing->setMonsterHealth($this->monsterHealth);
        $this->playerHealing->setCharacterHealth($this->characterHealth);
        $this->playerHealing->resurrect($previousAttackType);

        $this->characterHealth = $this->playerHealing->getCharacterHealth();
        $characterHealth       = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $characterHealth) {
            $this->characterHealth = $characterHealth;
        }

        $this->monsterHealth = $this->playerHealing->getMonsterHealth();

        $this->mergeMessages($this->playerHealing->getMessages());

        $this->playerHealing->clearMessages();
    }

    protected function playerBattleHealing(Character $character, string $previousAttackType) {
        $previousAttackType = $this->characterCacheData->getDataFromAttackCache($character, $previousAttackType);

        $this->playerHealing->setMonsterHealth($this->monsterHealth);
        $this->playerHealing->setCharacterHealth($this->characterHealth);
        $this->playerHealing->healInBattle($previousAttackType);

        $this->characterHealth = $this->playerHealing->getCharacterHealth();
        $characterHealth       = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $characterHealth) {
            $this->characterHealth = $characterHealth;
        }

        $this->monsterHealth = $this->playerHealing->getMonsterHealth();

        $this->mergeMessages($this->playerHealing->getMessages());

        $this->playerHealing->clearMessages();
    }

    protected function attackPlayer(ServerMonster $monster, Character $character, string $previousAttackType) {
        $attack = $monster->buildAttack();

        if (rand(1, 100) > (100 - 100 * $monster->getMonsterStat('criticality'))) {
            $this->addMessage($monster->getName() . ' grows enraged and lashes out with all fury! (Critical Strike!)', 'regular');

            $attack *= 2;
        }

        $playerCachedDefence = $this->characterCacheData->getCharacterDefenceAc($character);

        if (is_null($playerCachedDefence)) {
            $ac = $this->characterCacheData->getCachedCharacterData($character, 'ac');
        } else {
            $ac = $playerCachedDefence;
        }

        $attackType = (new AttackTypeValue($previousAttackType));

        if ($attackType->isDefend()) {
            $classBonus = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance')['chance'];
            $ac = $ac + $ac * $classBonus;
        }

        if ($ac >= $attack) {
            $this->addMessage('You blocked the enemies attack with your armour!', 'enemy-action');

            return;
        }

        $attack -= $ac;

        $this->addMessage('You reduced the incoming (Physical) damage with your armour by: ' . number_format($ac), 'player-action');

        $this->characterHealth -= $attack;

        $this->addMessage($monster->getName() . ' hits for: ' . number_format($attack), 'enemy-action');
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

            $this->addMessage($monster->getName() . '\'s enchantments glow, lashing out for: ' . number_format($maxAffixDamage), 'enemy-action');
        }
    }

    protected function castSpells(ServerMonster $monster, Character $character, string $previousAttackType) {
        if (!$this->canHit->canMonsterCastSpell($character, $monster, $this->isVoided)) {
            $this->addMessage($monster->getName() . '\'s Spells fizzle and fail to fire.', 'regular');

            return;
        }

        $spellDamage = $monster->getMonsterStat('spell_damage');


        if ($spellDamage > 0) {
            $spellEvasion = $this->characterCacheData->getCachedCharacterData($character, 'spell_evasion');
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
