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
     * Apply elemental matchup, matching resistance, and attacker penetration to an attack.
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

            $this->dealDamage($damage, $isMonster, 'regular', $attackingElementName, 0.0, 0.0, 0.0);

            return;
        }

        $matchingResistance = $this->matchingElementValue($defenderElements, $attackingElementName);
        $matchingPenetration = $this->matchingElementValue($attackerPenetration, $attackingElementName);
        $effectiveResistance = max(0, $matchingResistance - $matchingPenetration);

        if ($this->elementAttackData->isHalfDamage($defenderElements, $attackingElementName)) {
            $damage = floor(($damage * $attackingElementAmount) / 2);

            $this->dealDamage($damage, $isMonster, 'half', $attackingElementName, $matchingResistance, $matchingPenetration, $effectiveResistance);

            return;
        }

        if ($this->elementAttackData->isDoubleDamage($defenderElements, $attackingElementName)) {
            $damage = floor(($damage * $attackingElementAmount) * 2);

            $this->dealDamage($damage, $isMonster, 'double', $attackingElementName, $matchingResistance, $matchingPenetration, $effectiveResistance);

            return;
        }

        $damage = floor($damage * $attackingElementAmount);

        $this->dealDamage($damage, $isMonster, 'regular', $attackingElementName, $matchingResistance, $matchingPenetration, $effectiveResistance);
    }

    /**
     * Apply the effective resistance, update health, and record elemental battle messages.
     *
     * @param int $damage
     * @param bool $isMonster
     * @param string $type
     * @param string $elementName
     * @param float $matchingResistance
     * @param float $matchingPenetration
     * @param float $effectiveResistance
     * @return void
     */
    private function dealDamage(
        int $damage,
        bool $isMonster,
        string $type,
        string $elementName,
        float $matchingResistance,
        float $matchingPenetration,
        float $effectiveResistance,
    ): void {
        if (! $isMonster && $this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $newDamage = $this->applyResistanceToDamage($effectiveResistance, $damage);

        match ($type) {
            'half' => $this->halfDamageAttackMessages($isMonster, $damage),
            'double' => $this->doubleDamageAttackMessages($isMonster, $damage),
            default => $this->regularAttackMessages($isMonster, $damage),
        };

        $elementName = ucfirst(strtolower($elementName));
        $baseResistanceText = number_format($matchingResistance * 100, 2).'%';
        $penetrationText = number_format($matchingPenetration * 100, 2).'%';
        $effectiveResistanceText = number_format($effectiveResistance * 100, 2).'%';
        $isPenetratingResistance = $matchingPenetration > 0 && $matchingResistance > 0;

        $resistanceMessage = match (true) {
            $isPenetratingResistance && $isMonster => 'Your '.$elementName.' Atonement of '.$baseResistanceText.' is reduced by the enemy\'s '.$penetrationText.' '.$elementName.' Penetration, leaving '.$effectiveResistanceText.' effective resistance.',
            $isPenetratingResistance => 'The enemy\'s '.$elementName.' Atonement of '.$baseResistanceText.' is reduced by your '.$penetrationText.' '.$elementName.' Penetration, leaving '.$effectiveResistanceText.' effective resistance.',
            $isMonster => 'You resist '.$effectiveResistanceText.' of the enemy\'s '.$elementName.' Gem damage.',
            default => 'The enemy resists '.$effectiveResistanceText.' of your '.$elementName.' Gem damage.',
        };

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
     * Reduce elemental damage by the effective resistance value.
     *
     * @param float $effectiveResistance
     * @param int $damage
     * @return int
     */
    private function applyResistanceToDamage(float $effectiveResistance, int $damage): int
    {
        if ($effectiveResistance <= 0) {
            return $damage;
        }

        // Resistances are 4-decimal values; rounding first stops binary float drift (0.75 - 0.20) from truncating a whole point of damage.
        return floor(round($damage - ($damage * $effectiveResistance), 4));
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
