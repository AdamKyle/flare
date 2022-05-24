<?php


namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class DoubleHeal extends BattleBase {

    private int $characterHealth;

    private CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
    }

    public function setCharacterHealth(int $characterHealth): DoubleHeal
    {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function getCharacterHealth(): int
    {
        return $this->characterHealth;
    }

    public function handleHeal(Character $character, array $attackData) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (!rand(1, 100) > (100 - 100 * $extraActionData['chance'])) {
                return;
            }

            $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];
            $healFor     = $attackData['heal_for'];

            $this->addMessage('Your prayers were heard by The Creator and he grants you extra life!', 'regular');

            if (rand(1, 100) > (100 - 100 * $criticality)) {
                $this->addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'regular');

                $healFor *= 2;
            }

            $healFor = $healFor + $healFor * 0.15;

            $this->characterHealth += $healFor;

            $originalHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

            if ($this->characterHealth > $originalHealth) {
                $this->characterHealth = $originalHealth;
            }

            $this->addMessage('Your healing spell(s) heals for an additional: ' . number_format($healFor), 'player-action');
        }
    }
}
