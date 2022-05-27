<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;

class SetUpFight extends PvpMessages {

    private CharacterCacheData $characterCacheData;

    const ATTACKER = 'attacker';

    CONST DEFENDER = 'defender';

    public function __construct(CharacterCacheData $characterCacheData) {
        $this->characterCacheData = $characterCacheData;
    }

    public function setUp(Character $attacker, Character $defender) {

        $this->reduceSkills($attacker, $defender, self::ATTACKER);
        $this->reduceSkills($defender, $attacker, self::DEFENDER);
    }

    public function reduceSkills(Character $attacker, Character $defender, string $type) {
        $skillReduction = $this->characterCacheData->getCachedCharacterData($attacker, 'skill_reduction');

        if ($skillReduction > 0.0) {
            $defenderCache  = $this->characterCacheData->getCachedCharacterData($defender);

            foreach($defenderCache['skills'] as $skillName => $value) {
                $value = $value - $skillReduction;

                if ($value <= 0) {
                    $value = 0;
                }

                $defenderCache['skills'][$skillName] = $value;
            }

            $this->characterCacheData->updateCharacterSheetCache($defender, $defenderCache);

            if ($type === self::ATTACKER) {
                $this->createMessages([
                    [
                        'message' => $attacker->name . ' Causes you to thrash around blindly. (Core Skills reduced!)',
                        'type'    => 'enemy-action'
                    ],
                    [
                        'message' => 'You caused the enemy to thrash around like a lunatic. Skills Reduced!',
                        'type'    => 'player-action'
                    ]
                ], self::ATTACKER);
            } else {
                $this->createMessages([
                    [
                        'message' => $defender->name . ' Causes you to thrash around blindly. (Core Skills reduced!)',
                        'type'    => 'enemy-action'
                    ],
                    [
                        'message' => 'You caused the enemy to thrash around like a lunatic. Skills Reduced!',
                        'type'    => 'player-action'
                    ]
                ], self::DEFENDER);
            }
        }
    }

    protected function createMessages(array $messageObject, string $type) {
        if ($type === self::ATTACKER) {
            $this->addDefenderMessage($messageObject[0]['message'], $messageObject[0]['type']);
            $this->addAttackerMessage($messageObject[1]['message'], $messageObject[1]['type']);

            return;
        }

        if ($type === self::DEFENDER) {
            $this->addAttackerMessage($messageObject[0]['message'], $messageObject[0]['type']);
            $this->addDefenderMessage($messageObject[1]['message'], $messageObject[1]['type']);
        }
    }
}
