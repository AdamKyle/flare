<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\ServerFight\BattleBase;
use App\Flare\Traits\ElementAttackData;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class ElementalAttack extends BattleBase
{
    use ElementAttackData;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct($characterCacheData);
    }

    /**
     * Do the elemental attack.
     */
    public function doElementalAttack(array $defenderElements, array $attackerElements, int $damage, bool $isMonster = false): void
    {
        $highestElement = $this->getHighestElementDamage($attackerElements);

        $highestElementName = $this->getHighestElementName($attackerElements, $highestElement);

        if ($highestElementName === 'UNKNOWN') {
            return;
        }

        if ($highestElement <= 0) {
            return;
        }

        if (empty($defenderElements)) {
            $damage = floor($damage * $highestElement);

            $this->dealDamage($damage, 0, $highestElement, $isMonster, 'regular');

            return;
        }

        $highestDefendingElement = $this->getHighestElementDamage($defenderElements);

        if ($this->isHalfDamage($defenderElements, $highestElementName)) {

            $damage = floor(($damage * $highestElement) / 2);

            $this->dealDamage($damage, $highestDefendingElement, $highestElement, $isMonster, 'double');

            return;
        }

        if ($this->isDoubleDamage($defenderElements, $highestElementName)) {

            $damage = floor(($damage * $highestElement) * 2);

            $this->dealDamage($damage, $highestDefendingElement, $highestElement, $isMonster, 'half');

            return;
        }

        $damage = floor($damage * $highestElement);

        $this->dealDamage($damage, $highestDefendingElement, $highestElement, $isMonster, 'regular');
    }

    /**
     * Deal the elemental damage.
     *
     * @param  float  $highestDefendingElement  [defending]
     * @param  float  $highestElement  [attacking]
     * @param  string  $type  - hald, double or regular
     * @return void
     */
    protected function dealDamage(int $damage, float $highestDefendingElement, float $highestElement, bool $isMonster, string $type)
    {
        $damage = floor($damage * $highestElement);

        if (!$isMonster) {
            if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
            }
        }

        $newDamage = $this->applyResistanceToDamage($highestDefendingElement, $damage, $isMonster);

        switch ($type) {
            case 'half':
                $this->halfDamageAttackMessages($isMonster, $damage);
                break;
            case 'double':
                $this->doubleDamageAttackMessages($isMonster, $damage);
                break;
            case 'regular':
            default:
                $this->regularAttackMessages($isMonster, $damage);
        }

        $this->addMessage(
            $isMonster ?
                'You manage to resist: ' . number_format($damage - $newDamage) . ' (' . number_format($highestDefendingElement * 100, 2) . '%) damage from the enemies bloody gems!' :
                'The enemy resists: ' . number_format($damage - $newDamage) . ' (' . number_format($highestDefendingElement * 100, 2) . '%) damage from your gems!',
            ($isMonster ? 'regular' : 'enemy-action')
        );

        if ($isMonster) {
            $this->characterHealth -= $newDamage;
        } else {
            $this->monsterHealth -= $newDamage;
        }
    }

    /**
     * Apply the resistance to the damage.
     */
    protected function applyResistanceToDamage(float $highestDefendingElement, int $damage, bool $isMonster = false): int
    {

        if ($highestDefendingElement > 0) {
            $amountToResist = $damage * $highestDefendingElement;

            $damage = $damage - $amountToResist;
        }

        return $damage;
    }

    /**
     * Create messages when the damage is half
     */
    protected function halfDamageAttackMessages(bool $isMonster, int $damage): void
    {

        if (! $isMonster) {
            $this->addMessage('The sockets on your gear glow with the radiance of the gems attached.', 'player-action');
            $this->addMessage('The enemies element is stonger then yours, you only do half damage for: ' . number_format($damage), 'player-action');

            return;
        }

        $this->addMessage('The enemies grip tightens around the gems they carry, dripping in blood.', 'enemy-action');
        $this->addMessage('Your gems are stronger the enemies, the enemy only does half damage for: ' . number_format($damage), 'enemy-action');
    }

    /**
     * Create messages when the damage is double
     */
    protected function doubleDamageAttackMessages(bool $isMonster, int $damage): void
    {
        if (! $isMonster) {
            $this->addMessage('The sockets on your gear glow with the radiance of the gems attached.', 'player-action');
            $this->addMessage('The enemies element is weaker then yours, you do double damage for: ' . number_format($damage), 'player-action');

            return;
        }

        $this->addMessage('The enemies grip tightens around the gems they carry, dripping in blood.', 'enemy-action');
        $this->addMessage('Your gems are weaker the enemies, the enemy does double damage for: ' . number_format($damage), 'enemy-action');
    }

    /**
     * Create regular messages when damage is regular
     */
    protected function regularAttackMessages(bool $isMonster, int $damage): void
    {
        if (! $isMonster) {
            $this->addMessage('The sockets on your gear glow with the radiance of the gems attached.', 'player-action');
            $this->addMessage('The gems lash out towards the enemy dealing: ' . number_format($damage), 'player-action');

            return;
        }

        $this->addMessage('The enemies grip tightens around the gems they carry, dripping in blood.', 'enemy-action');
        $this->addMessage('The enemies gems rage towards you dealing: ' . number_format($damage), 'enemy-action');
    }
}
