<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use PHPUnit\TextUI\Exception;

class SetUpFight extends PvpMessages {

    private CharacterCacheData $characterCacheData;

    const ATTACKER = 'attacker';

    CONST DEFENDER = 'defender';

    public function __construct(CharacterCacheData $characterCacheData) {
        $this->characterCacheData = $characterCacheData;
    }

    public function setUp(Character $attacker, Character $defender) {

        $attackerResult = $this->reduceSkills($attacker, $defender, self::ATTACKER);
        $defenderResult = $this->reduceSkills($defender, $attacker, self::DEFENDER);

        if ($attackerResult) {
            $this->addAttackerMessage('You caused the enemy to thrash around like a lunatic. Skills Reduced!', 'player-action');
            $this->addDefenderMessage($attacker->name . ' Causes you to thrash around blindly. (Core Skills reduced!)', 'enemy-action');
        }

        if ($defenderResult) {
            $this->addDefenderMessage('You caused the enemy to thrash around like a lunatic. Skills Reduced!', 'player-action');
            $this->addAttackerMessage($defender->name . ' Causes you to thrash around blindly. (Core Skills reduced!)', 'enemy-action');
        }
    }

    public function reduceSkills(Character $attacker, Character $defender, string $type) {
        $skillReduction = $this->characterCacheData->getCachedCharacterData($attacker, 'skill_reduction');

        if ($skillReduction > 0.0) {

            $defenderCache = $this->characterCacheData->getCharacterSheetCache($defender);

            foreach($defenderCache['skills'] as $skillName => $value) {
                $value = $value - $skillReduction;

                if ($value <= 0) {
                    $value = 0;
                }

                $defenderCache['skills'][$skillName] = $value;
            }

            $this->characterCacheData->updateCharacterSheetCache($defender, $defenderCache);

            return true;
        }

        return false;
    }

}
