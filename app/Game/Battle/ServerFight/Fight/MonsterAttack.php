<?php

namespace App\Game\Battle\ServerFight\Fight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Counter;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\PlayerHealing;
use App\Game\Battle\ServerFight\Monster\MonsterSpecialAttack;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\AttackType;

class MonsterAttack extends BattleBase
{
    private int $lastRolledAttack = 0;

    private PlayerHealing $playerHealing;

    private Entrance $entrance;

    private CanHit $canHit;

    private ElementalAttack $elementalAttack;

    private MonsterSpecialAttack $monsterSpecialAttack;

    private Counter $counter;

    public function __construct(
        CharacterCacheData $characterCacheData,
        ChanceCalculator $chanceCalculator,
        RandomNumberGenerator $randomNumberGenerator,
        PlayerHealing $playerHealing,
        Entrance $entrance,
        CanHit $canHit,
        ElementalAttack $elementalAttack,
        MonsterSpecialAttack $monsterSpecialAttack,
        Counter $counter,
    ) {
        parent::__construct($characterCacheData, $chanceCalculator, $randomNumberGenerator);

        $this->entrance = $entrance;
        $this->canHit = $canHit;
        $this->playerHealing = $playerHealing;
        $this->elementalAttack = $elementalAttack;
        $this->monsterSpecialAttack = $monsterSpecialAttack;
        $this->counter = $counter;
    }

    public function getLastRolledAttack(): int
    {
        return $this->lastRolledAttack;
    }

    public function setIsCharacterVoided(bool $isVoided): MonsterAttack
    {
        $this->isVoided = $isVoided;

        return $this;
    }

    public function monsterAttack(ServerMonster $monster, Character $character, string $previousAttackType)
    {
        if ($this->canHit->canMonsterHitPlayer($character, $monster, $this->isVoided)) {
            $this->attackPlayer($monster, $character, $previousAttackType);

            $this->playerBattleHealing($character, $previousAttackType);
            $this->vampiricHealing($character);

            $this->doPlayerCounterMonster($character, $monster);
        } else {
            $this->addMessage($monster->getName().' misses!', 'enemy-action');
        }

        if ($this->monsterHealth <= 0) {
            return;
        }

        if (! $this->isEnemyVoided) {
            $this->fireEnchantments($monster, $character);
            $this->playerBattleHealing($character, $previousAttackType);
            $this->vampiricHealing($character);
            $this->castSpells($monster, $character, $previousAttackType);
            $this->playerBattleHealing($character, $previousAttackType);
            $this->vampiricHealing($character);
        }

        $this->monsterElementalAttack($monster, $character);
        $this->playerBattleHealing($character, $previousAttackType);
        $this->vampiricHealing($character);
        $this->monsterSpecialAttack($monster, $character);
        $this->playerBattleHealing($character, $previousAttackType);
        $this->vampiricHealing($character);

        if ($this->characterHealth <= 0) {
            $this->playerResurrection($character, $previousAttackType);
        }
    }

    private function monsterElementalAttack(ServerMonster $monster, Character $character)
    {
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

            $this->elementalAttack->setMonsterHealth($this->monsterHealth);
            $this->elementalAttack->setCharacterHealth($this->characterHealth);

            $this->elementalAttack->doElementalAttack($elementalData, $monster->getElementData(), $monster->buildAttack(), true);

            $this->characterHealth = $this->elementalAttack->getCharacterHealth();
            $this->monsterHealth = $this->elementalAttack->getMonsterHealth();

            $this->mergeMessages($this->elementalAttack->getMessages());

            $this->elementalAttack->clearMessages();
        }
    }

    private function monsterSpecialAttack(ServerMonster $monster, Character $character)
    {
        if (
            $monster->getMonsterStat('is_raid_monster') ||
            $monster->getMonsterStat('is_raid_boss') ||
            $monster->canMonsterUseElementalAttack()
        ) {
            $ac = $this->characterCacheData->getCachedCharacterData($character, 'ac');

            $this->monsterSpecialAttack->setMonsterHealth($this->monsterHealth);
            $this->monsterSpecialAttack->setCharacterHealth($this->characterHealth);

            $specialAttackType = $monster->getMonsterStat('raid_special_attack_type');
            $damageStatAmount = $monster->getMonsterStat($monster->getMonsterStat('damage_stat'));

            if (! is_null($specialAttackType)) {
                $this->monsterSpecialAttack->doSpecialAttack($specialAttackType, $damageStatAmount, $ac);
            }

            $this->characterHealth = $this->monsterSpecialAttack->getCharacterHealth();
            $this->monsterHealth = $this->monsterSpecialAttack->getMonsterHealth();

            $this->mergeMessages($this->monsterSpecialAttack->getMessages());

            $this->monsterSpecialAttack->clearMessages();
        }
    }

    private function playerResurrection(Character $character, string $previousAttackType)
    {
        $previousAttackType = $this->characterCacheData->getDataFromAttackCache($character, $previousAttackType);

        $this->playerHealing->setMonsterHealth($this->monsterHealth);
        $this->playerHealing->setCharacterHealth($this->characterHealth);
        $this->playerHealing->resurrect($previousAttackType);

        $this->characterHealth = $this->playerHealing->getCharacterHealth();
        $characterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $characterHealth) {
            $this->characterHealth = $characterHealth;
        }

        $this->monsterHealth = $this->playerHealing->getMonsterHealth();

        $this->mergeMessages($this->playerHealing->getMessages());

        $this->playerHealing->clearMessages();
    }

    private function playerBattleHealing(Character $character, string $previousAttackType)
    {
        $previousAttackType = $this->characterCacheData->getDataFromAttackCache($character, $previousAttackType);

        $this->playerHealing->setMonsterHealth($this->monsterHealth);
        $this->playerHealing->setCharacterHealth($this->characterHealth);
        $this->playerHealing->healInBattle($character, $previousAttackType);

        $this->characterHealth = $this->playerHealing->getCharacterHealth();
        $characterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $characterHealth) {
            $this->characterHealth = $characterHealth;
        }

        $this->monsterHealth = $this->playerHealing->getMonsterHealth();

        $this->mergeMessages($this->playerHealing->getMessages());

        $this->playerHealing->clearMessages();
    }

    private function vampiricHealing(Character $character)
    {

        $this->playerHealing->setMonsterHealth($this->monsterHealth);
        $this->playerHealing->setCharacterHealth($this->characterHealth);
        $this->playerHealing->lifeSteal($character);

        $this->characterHealth = $this->playerHealing->getCharacterHealth();
        $characterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $characterHealth) {
            $this->characterHealth = $characterHealth;
        }

        $this->monsterHealth = $this->playerHealing->getMonsterHealth();

        $this->mergeMessages($this->playerHealing->getMessages());

        $this->playerHealing->clearMessages();
    }

    private function attackPlayer(ServerMonster $monster, Character $character, string $previousAttackType)
    {
        $attack = $monster->buildAttack();

        if ($this->chanceCalculator->passesPercentage($monster->getMonsterStat('criticality') * 100)) {
            $this->addMessage($monster->getName().' grows enraged and lashes out with all fury! (Critical Strike!)', 'regular');

            $attack *= 2;
        }

        $this->lastRolledAttack = $attack;

        $playerCachedDefence = $this->characterCacheData->getCharacterDefenceAc($character);

        if (is_null($playerCachedDefence)) {
            $ac = $this->characterCacheData->getCachedCharacterData($character, 'ac');
        } else {
            $ac = $playerCachedDefence;
        }

        $attackType = (AttackType::from($previousAttackType));

        if ($attackType->isDefend()) {
            $classBonus = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance')['chance'];
            $ac = $ac + $ac * $classBonus;
        }

        if ($ac >= $attack) {
            $this->addMessage('You blocked the enemies attack with your armour!', 'enemy-action');

            return;
        }

        $attack -= $ac;

        $this->addMessage('You reduced the incoming (Physical) damage with your armour by: '.number_format($ac), 'player-action');

        $this->characterHealth -= $attack;

        $this->addMessage($monster->getName().' hits for: '.number_format($attack), 'enemy-action');
    }

    private function fireEnchantments(ServerMonster $monster, Character $character)
    {
        $maxAffixDamage = $monster->getMonsterStat('max_affix_damage');
        $maxAffixDamage = $this->randomNumberGenerator->numberBetween(1, $maxAffixDamage);
        $damageReduction = $this->characterCacheData->getCachedCharacterData($character, 'affix_damage_reduction');

        $maxAffixDamage = $maxAffixDamage - $maxAffixDamage * $damageReduction;

        if ($damageReduction > 0.0) {
            $this->addMessage('Your rings negate some of the enemy\'s enchantment damage.', 'player-action');
        }

        if ($maxAffixDamage > 0) {
            $this->characterHealth -= $maxAffixDamage;

            $this->addMessage($monster->getName().'\'s enchantments glow, lashing out for: '.number_format($maxAffixDamage), 'enemy-action');
        }
    }

    /**
     * Let the player counter the monster's attack.
     */
    private function doPlayerCounterMonster(Character $character, ServerMonster $monster): void
    {
        $this->counter->setCharacterHealth($this->characterHealth);
        $this->counter->setMonsterHealth($this->monsterHealth);
        $this->counter->setIsAttackerVoided($this->isVoided);
        $this->counter->playerCounter($character, $monster);

        $this->mergeMessages($this->counter->getMessages());

        $this->characterHealth = $this->counter->getCharacterHealth();
        $this->monsterHealth = $this->counter->getMonsterHealth();

        $this->counter->clearMessages();
    }

    private function castSpells(ServerMonster $monster, Character $character, string $previousAttackType)
    {
        if (! $this->canHit->canMonsterCastSpell($character, $monster, $this->isVoided)) {
            $this->addMessage($monster->getName().'\'s Spells fizzle and fail to fire.', 'regular');

            return;
        }

        $spellDamage = $monster->getMonsterStat('spell_damage');

        if ($spellDamage > 0) {
            $spellEvasion = $this->characterCacheData->getCachedCharacterData($character, 'spell_evasion');
            if ($spellEvasion >= 1 || $this->chanceCalculator->passesPercentage($spellEvasion * 100)) {
                $this->addMessage('You evade the enemy\'s spells!', 'player-action');

                return;
            }

            $criticality = $monster->getMonsterStat('criticality');

            if ($this->chanceCalculator->passesPercentage($criticality * 100)) {
                $this->addMessage($monster->getName().' With a fury of hatred their spells fly viciously at you! (Critical Strike!)', 'regular');

                $spellDamage *= 2;
            }

            if ($previousAttackType === 'defend') {
                if ($this->characterCacheData->getCachedCharacterData($character, 'ac') >= $spellDamage) {
                    $this->addMessage('You managed to block the enemy\'s spells with your armour!', 'player-action');
                }
            }

            $this->characterHealth -= $spellDamage;

            $this->addMessage($monster->getName().'\'s spells burst toward you doing: '.number_format($spellDamage), 'enemy-action');
        }
    }
}
