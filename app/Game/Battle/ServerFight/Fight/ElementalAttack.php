<?php

namespace App\Game\Battle\ServerFight\Fight;

use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\ElementAttackData;

class ElementalAttack extends BattleBase
{
    /**
     * @param CharacterCacheData $characterCacheData
     * @param ChanceCalculator $chanceCalculator
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param ElementAttackData $elementAttackData
     */
    public function __construct(
        CharacterCacheData $characterCacheData,
        ChanceCalculator $chanceCalculator,
        RandomNumberGenerator $randomNumberGenerator,
        private readonly ElementAttackData $elementAttackData,
    ) {
        parent::__construct($characterCacheData, $chanceCalculator, $randomNumberGenerator);
    }

    /**
     * Apply elemental matchup, matching resistance, and player penetration to an attack.
     *
     * @param array $defenderElements
     * @param array $attackerElements
     * @param int $damage
     * @param bool $isMonster
     * @param array $attackerPenetration
     * @return void
     */
    public function doElementalAttack(
        array $defenderElements,
        array $attackerElements,
        int $damage,
        bool $isMonster = false,
        array $attackerPenetration = [],
    ): void {
        $attackingElementAmount = $this->elementAttackData->getHighestElementDamage($attackerElements);
        $attackingElementName = $this->elementAttackData->getHighestElementName($attackerElements, $attackingElementAmount);

        if ($attackingElementName === 'UNKNOWN') {
            return;
        }

        if ($attackingElementAmount <= 0) {
            return;
        }

        if (empty($defenderElements)) {
            $damage = floor($damage * $attackingElementAmount);

            $this->dealDamage($damage, 0, $isMonster, 'regular');

            return;
        }

        $matchingResistance = $this->matchingElementValue($defenderElements, $attackingElementName);
        $matchingPenetration = $isMonster ? 0.0 : $this->matchingElementValue($attackerPenetration, $attackingElementName);
        $effectiveResistance = max(0, $matchingResistance - $matchingPenetration);

        if ($this->elementAttackData->isHalfDamage($defenderElements, $attackingElementName)) {
            $damage = floor(($damage * $attackingElementAmount) / 2);

            $this->dealDamage($damage, $effectiveResistance, $isMonster, 'half', $attackingElementName, $matchingPenetration);

            return;
        }

        if ($this->elementAttackData->isDoubleDamage($defenderElements, $attackingElementName)) {
            $damage = floor(($damage * $attackingElementAmount) * 2);

            $this->dealDamage($damage, $effectiveResistance, $isMonster, 'double', $attackingElementName, $matchingPenetration);

            return;
        }

        $damage = floor($damage * $attackingElementAmount);

        $this->dealDamage($damage, $effectiveResistance, $isMonster, 'regular', $attackingElementName, $matchingPenetration);
    }

    /**
     * Apply matching resistance, update health, and record elemental battle messages.
     *
     * @param int $damage
     * @param float $matchingResistance
     * @param bool $isMonster
     * @param string $type
     * @param string $elementName
     * @param float $penetration
     * @return void
     */
    private function dealDamage(
        int $damage,
        float $matchingResistance,
        bool $isMonster,
        string $type,
        string $elementName = 'Unknown',
        float $penetration = 0.0,
    ): void {
        if (! $isMonster && $this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $newDamage = $this->applyResistanceToDamage($matchingResistance, $damage);

        match ($type) {
            'half' => $this->halfDamageAttackMessages($isMonster, $damage),
            'double' => $this->doubleDamageAttackMessages($isMonster, $damage),
            default => $this->regularAttackMessages($isMonster, $damage),
        };

        $resistanceMessage = $isMonster
            ? 'You resist '.number_format($matchingResistance * 100, 2).'% of the enemy\'s '.$elementName.' Gem damage.'
            : 'The enemy resists '.number_format($matchingResistance * 100, 2).'% of your '.$elementName.' Gem damage after '.number_format($penetration * 100, 2).'% '.$elementName.' penetration.';

        $this->addMessage($resistanceMessage, $isMonster ? 'regular' : 'enemy-action');

        $this->addMessage(
            $isMonster ?
                'You take: '.number_format($newDamage).' in damage from the enemies bloody gems!' :
                'The enemy takes: '.number_format($newDamage).' in damage from your gems!',
            ($isMonster ? 'enemy-action' : 'player-action')
        );

        if ($isMonster) {
            $this->characterHealth -= $newDamage;

            return;
        }

        $this->monsterHealth -= $newDamage;
    }

    /**
     * Reduce elemental damage by the matching resistance value.
     *
     * @param float $matchingResistance
     * @param int $damage
     * @return int
     */
    private function applyResistanceToDamage(float $matchingResistance, int $damage): int
    {

        if ($matchingResistance <= 0) {
            return $damage;
        }

        return $damage - ($damage * $matchingResistance);
    }

    /**
     * Return the defender or attacker value for the exact attacking element.
     *
     * @param array $elements
     * @param string $elementName
     * @return float
     */
    private function matchingElementValue(array $elements, string $elementName): float
    {
        foreach ($elements as $name => $value) {
            if (strtolower($name) === strtolower($elementName)) {
                return $value;
            }
        }

        return 0.0;
    }

    /**
     * Record messages for a weak elemental matchup.
     *
     * @param bool $isMonster
     * @param int $damage
     * @return void
     */
    private function halfDamageAttackMessages(bool $isMonster, int $damage): void
    {

        if (! $isMonster) {
            $this->addMessage('The sockets on your gear glow with the radiance of the gems attached.', 'player-action');
            $this->addMessage('The enemies element is stonger then yours, you only do half damage for: '.number_format($damage), 'player-action');

            return;
        }

        $this->addMessage('The enemies grip tightens around the gems they carry, dripping in blood.', 'enemy-action');
        $this->addMessage('Your gems are stronger the enemies, the enemy only does half damage for: '.number_format($damage), 'enemy-action');
    }

    /**
     * Record messages for a strong elemental matchup.
     *
     * @param bool $isMonster
     * @param int $damage
     * @return void
     */
    private function doubleDamageAttackMessages(bool $isMonster, int $damage): void
    {
        if (! $isMonster) {
            $this->addMessage('The sockets on your gear glow with the radiance of the gems attached.', 'player-action');
            $this->addMessage('The enemies element is weaker then yours, you do double damage for: '.number_format($damage), 'player-action');

            return;
        }

        $this->addMessage('The enemies grip tightens around the gems they carry, dripping in blood.', 'enemy-action');
        $this->addMessage('Your gems are weaker the enemies, the enemy does double damage for: '.number_format($damage), 'enemy-action');
    }

    /**
     * Record messages for a neutral elemental matchup.
     *
     * @param bool $isMonster
     * @param int $damage
     * @return void
     */
    private function regularAttackMessages(bool $isMonster, int $damage): void
    {
        if (! $isMonster) {
            $this->addMessage('The sockets on your gear glow with the radiance of the gems attached.', 'player-action');
            $this->addMessage('The gems lash out towards the enemy dealing: '.number_format($damage), 'player-action');

            return;
        }

        $this->addMessage('The enemies grip tightens around the gems they carry, dripping in blood.', 'enemy-action');
        $this->addMessage('The enemies gems rage towards you dealing: '.number_format($damage), 'enemy-action');
    }
}
