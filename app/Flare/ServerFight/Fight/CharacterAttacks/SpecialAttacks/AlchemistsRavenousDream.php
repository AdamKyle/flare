<?php


namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class AlchemistsRavenousDream extends BattleBase {

    private int $characterHealth;

    private int $monsterHealth;

    private CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
    }

    public function setCharacterHealth(int $characterHealth): AlchemistsRavenousDream
    {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): AlchemistsRavenousDream
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

        if ($extraActionData['has_item']) {

            if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
            }

            $this->addMessage('The world around you fades to blackness, your eyes glow red with rage. The enemy trembles.', 'regular');

            $damage = $this->characterCacheData->getCachedCharacterData($character, 'int_modded') * 0.10;

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            $this->doBaseAttack($damage);
        }
    }

    protected function doBaseAttack(int $damage) {
        $this->monsterHealth -= $damage;

        $this->addMessage('You hit for (Arcane Alchemist Ravenous Dream): ' . number_format($damage), 'player-action');
    }

    protected function multiAttack(Character $character, array $attackData, int $damage) {
        $times         = rand(2, 6);
        $originalTimes = $times;



        while ($times > 0) {
            if ($times === $originalTimes) {
                $this->monsterHealth -= $damage;

                $this->addMessage('You hit for (Arcane Alchemist Ravenous Dream): ' . number_format($damage), 'player-action');
            } else {
                $damage = $this->characterCacheData->getCachedCharacterData($character, 'int_modded') * 0.10;

                if ($attackData['damage_deduction'] > 0.0) {
                    $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

                    $damage = $damage - $damage * $attackData['damage_deduction'];
                }

                if ($damage >= 1) {
                    $this->addMessage('The earth shakes as you cause a multitude of explosions to engulf the enemy.', 'regular');

                    $this->monsterHealth -= $damage;

                    $this->addMessage('You hit for (Arcane Alchemist Ravenous Dream): ' . number_format($damage), 'player-action');
                }
            }
        }
    }
}
