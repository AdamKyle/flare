<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Traits\ElementAttackData;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\LocationType;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class SecondaryAttacks extends BattleBase
{
    use ElementAttackData;

    private Affixes $affixes;

    public function __construct(CharacterCacheData $characterCacheData, Affixes $affixes)
    {
        parent::__construct($characterCacheData);

        $this->affixes = $affixes;
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

        $damage = $this->affixes->getCharacterAffixDamage($this->attackData, $resistance);

        $this->mergeMessages($this->affixes->getMessages());

        if ($damage > 0) {
            $this->monsterHealth = $this->monsterHealth - $damage;
        }

        $this->affixes->clearMessages();
    }

    public function classSpecialtyDamage()
    {
        $special = $this->attackData['special_damage'];

        if (empty($special)) {
            return;
        }

        if ($special['required_attack_type'] === $this->attackData['attack_type']) {

            $this->monsterHealth -= $special['damage'];

            $this->addMessage('Your class special: ' . $special['name'] . ' fires off and you do: ' . number_format($special['damage']) . ' damage to the enemy!', 'player-action');
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

        $lifeStealingDamage = $this->affixes->getAffixLifeSteal($character, $this->attackData, $this->monsterHealth, $resistance);

        if (! is_null($monster)) {
            $monsterData = $monster->getMonster();
            $lifeStealingResistance = $monsterData['life_stealing_resistance'];
            $damageResistance = 0.0;

            if (($monsterData['is_raid_monster'] || $monsterData['is_raid_boss']) && ! is_null($lifeStealingResistance)) {

                $damageResistance = $lifeStealingResistance;
            }

            if ($damageResistance > 0) {
                $lifeStealingDamage -= $lifeStealingDamage * $damageResistance;

                $this->addMessage('The enemy manages to resist (' . ($damageResistance * 100) . '%) some of the life stealing damage!', 'enemy-action');
            }
        }

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();

        if ($lifeStealingDamage > 0.0 && $this->isAtRankedFightLocation($character)) {
            $lifeStealingDamage = min($lifeStealingDamage, .50);
        }

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

            $this->addMessage('Your rings hit for: ' . number_format($ringDamage), 'player-action');
        }
    }

    public function dealElementalDamage(Character $character, ?ServerMonster $monster = null, bool $canDoElementalDamage = false)
    {

        if (! $canDoElementalDamage) {
            return;
        }

        if ($this->attackData['attack_type'] === AttackTypeValue::DEFEND) {
            return;
        }

        $damageType = match ($this->attackData['attack_type']) {
            AttackTypeValue::ATTACK, AttackTypeValue::ATTACK_AND_CAST => 'weapon_attack',
            AttackTypeValue::CAST, AttackTypeValue::CAST_AND_ATTACK => 'spell_attack',
        };

        $this->elementalAttack($character, $monster, $damageType);
    }

    protected function getDamageForElementalDamage(): int
    {
        if (isset($this->attackData['weapon_damage'])) {
            return $this->attackData['weapon_damage'];
        }

        if (isset($this->attackData['spell_damage'])) {
            return $this->attackData['spell_damage'];
        }

        return 0;
    }

    protected function isAtRankedFightLocation(Character $character): bool
    {

        $location = Location::where('x', $character->map->x_position)
            ->where('y', $character->map->y_position)
            ->where('game_map_id', $character->map->game_map_id)
            ->where('type', LocationType::UNDERWATER_CAVES)
            ->first();

        return ! is_null($location) ** $character->classType()->isVampire();
    }
}
