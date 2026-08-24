<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Battle\ServerFight\Fight\Affixes;
use App\Game\Battle\ServerFight\Fight\ElementalAttack;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\AttackType;

class SecondaryAttacks extends BattleBase
{
    private Affixes $affixes;

    private ElementalAttack $elementalAttack;

    public function __construct(CharacterCacheData $characterCacheData, ChanceCalculator $chanceCalculator, RandomNumberGenerator $randomNumberGenerator, Affixes $affixes, ElementalAttack $elementalAttack)
    {
        parent::__construct($characterCacheData, $chanceCalculator, $randomNumberGenerator);

        $this->affixes = $affixes;
        $this->elementalAttack = $elementalAttack;
    }

    public function setAttackData(array $attackData)
    {
        $this->attackData = $attackData;
    }

    public function setIsCharacterVoided(bool $voided)
    {
        $this->isVoided = $voided;
    }

    public function setIsEnemyEntranced(bool $entranced)
    {
        $this->isEnemyEntranced = $entranced;
    }

    public function doSecondaryAttack(Character $character, ?ServerMonster $monster = null, float $affixReduction = 0.0)
    {
        $this->classSpecialtyDamage();

        $this->dealElementalDamage($character, $monster, true);

        if (! $this->isVoided) {

            if ($this->isEnemyEntranced) {
                $affixReduction = 0.0;
            }

            $this->affixLifeStealingDamage($character, $monster, $affixReduction);

            $this->affixDamage($character, $monster, $affixReduction);

            $this->ringDamage();
        } else {
            $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
        }
    }

    public function affixDamage(Character $character, ?ServerMonster $monster = null)
    {

        $resistance = 0.0;

        if (! is_null($monster)) {
            $resistance = $monster->getMonsterStat('affix_resistance');
        }

        $this->affixes->setIsRaidBoss($this->isRaidBoss);

        $damage = $this->affixes->getCharacterAffixDamage($this->attackData, $resistance);

        $this->mergeMessages($this->affixes->getMessages());

        if ($damage > 0) {
            $this->monsterHealth = $this->monsterHealth - $damage;
        }

        $this->affixes->clearMessages();
    }

    public function classSpecialtyDamage()
    {
        $special = $this->attackData['special_damage'] ?? null;

        if (empty($special)) {
            return;
        }

        if ($special['required_attack_type'] === $this->attackData['attack_type']) {

            $this->monsterHealth -= $special['damage'];

            $this->addMessage('Your class special: '.$special['name'].' fires off and you do: '.number_format($special['damage']).' damage to the enemy!', 'player-action');
        }
    }

    public function affixLifeStealingDamage(Character $character, ?ServerMonster $monster = null)
    {

        if ($this->monsterHealth <= 0) {
            return;
        }

        $resistance = 0.0;

        if (! is_null($monster) && ! $this->isEnemyEntranced) {
            $resistance = $monster->getMonsterStat('affix_resistance');
        }

        if ($this->isEnemyEntranced) {
            $this->affixes->setEntranced();
        }

        $this->affixes->setIsRaidBoss($this->isRaidBoss);

        $currentMonsterHealth = $this->monsterHealth;
        $lifeStealingDamage = $this->affixes->getAffixLifeSteal($character, $this->attackData, $this->monsterHealth, $resistance);

        if (! is_null($monster)) {
            $monsterData = $monster->getMonster();
            $lifeStealingResistance = $monsterData['life_stealing_resistance'] ?? null;

            if (! is_null($lifeStealingResistance) && $lifeStealingResistance > 0 && $lifeStealingDamage > 0) {
                $attemptedPercent = ($lifeStealingDamage / $currentMonsterHealth) * 100;
                $lifeStealingDamage -= $lifeStealingDamage * $lifeStealingResistance;
                $finalPercent = ($lifeStealingDamage / $currentMonsterHealth) * 100;

                $this->addMessage('The enemy resisted your attempt to steal '.number_format($attemptedPercent, 2).'% of their health and instead you stole '.number_format($finalPercent, 2).'%, dealing '.number_format($lifeStealingDamage).' damage.', 'enemy-action');
            }
        }

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();

        if ($lifeStealingDamage > 0) {
            $this->monsterHealth -= $lifeStealingDamage;
            $this->characterHealth += $lifeStealingDamage;

            $maxCharacterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

            if ($this->characterHealth >= $maxCharacterHealth) {
                $this->characterHealth = $maxCharacterHealth;
            }
        }
    }

    public function ringDamage()
    {
        $ringDamage = $this->attackData['ring_damage'];

        if ($ringDamage > 0) {
            $this->monsterHealth -= ($ringDamage - $ringDamage * $this->attackData['damage_deduction']);

            $this->addMessage('Your rings hit for: '.number_format($ringDamage), 'player-action');
        }
    }

    public function dealElementalDamage(Character $character, ?ServerMonster $monster = null, bool $canDoElementalDamage = false)
    {

        if (! $canDoElementalDamage) {
            return;
        }

        if ($this->attackData['attack_type'] === AttackType::DEFEND->value) {
            return;
        }

        $damageType = match ($this->attackData['attack_type']) {
            AttackType::ATTACK->value, AttackType::ATTACK_AND_CAST->value => 'weapon_attack',
            AttackType::CAST->value, AttackType::CAST_AND_ATTACK->value => 'spell_attack',
        };

        $this->applyElementalAttack($character, $monster, $damageType);
    }

    /**
     * Apply the character's elemental gem damage against the monster.
     */
    private function applyElementalAttack(Character $character, ServerMonster $monster, string $damageType): void
    {
        $this->elementalAttack->setMonsterHealth($this->monsterHealth);
        $this->elementalAttack->setCharacterHealth($this->characterHealth);
        $this->elementalAttack->setIsRaidBoss($this->isRaidBoss);

        $characterElementalData = $this->characterCacheData->getCachedCharacterData($character, 'elemental_atonement');

        if (is_null($characterElementalData)) {
            return;
        }

        $characterElementalData = $characterElementalData['atonements'];

        $damage = $this->characterCacheData->getCachedCharacterData($character, $damageType);

        $this->elementalAttack->doElementalAttack($monster->getElementData(), $characterElementalData, $damage);

        $this->mergeMessages($this->elementalAttack->getMessages());

        $this->characterHealth = $this->elementalAttack->getCharacterHealth();
        $this->monsterHealth = $this->elementalAttack->getMonsterHealth();

        $this->elementalAttack->clearMessages();
    }

    private function getDamageForElementalDamage(): int
    {
        if (isset($this->attackData['weapon_damage'])) {
            return $this->attackData['weapon_damage'];
        }

        if (isset($this->attackData['spell_damage'])) {
            return $this->attackData['spell_damage'];
        }

        return 0;
    }
}
