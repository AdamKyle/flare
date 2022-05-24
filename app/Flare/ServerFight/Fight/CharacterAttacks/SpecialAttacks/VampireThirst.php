<?php


namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class VampireThirst extends BattleBase {

    private int $characterHealth;

    private int $monsterHealth;

    private CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
    }

    public function setCharacterHealth(int $characterHealth): VampireThirst
    {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): VampireThirst
    {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function getCharacterHealth(): int
    {
        return $this->characterHealth;
    }

    public function getMonsterHealth(): int
    {
        return $this->monsterHealth;
    }

    public function handleAttack(Character $character, array $attackData) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
            return;
        }

        $dur    = $this->characterCacheData->getCachedCharacterData($character, 'dur_modded');
        $damage = $dur + $dur * 0.15;

        $this->addMessage('There is a thirst, child, it\'s in your soul! Lash out and kill!', 'regular');

        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

            $damage = $damage - $damage * $attackData['damage_deduction'];
        }

        $this->doBaseAttack($character, $damage);
    }

    protected function doBaseAttack(Character $character, int $damage) {
        $this->monsterHealth   -= $damage;
        $this->characterHealth += $damage;

        $maxHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $maxHealth) {
            $this->characterHealth = $maxHealth;
        }

        $this->addMessage('You hit for (thirst!) (and healed for) ' . number_format($damage), 'player-action');
    }
}
