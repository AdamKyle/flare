<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\Ambush;
use App\Flare\ServerFight\Fight\Voidance;
use PHPUnit\TextUI\Exception;

class SetUpFight extends PvpMessages {

    private CharacterCacheData $characterCacheData;

    private Voidance $voidance;

    private Ambush $ambush;

    const ATTACKER = 'attacker';

    CONST DEFENDER = 'defender';

    private $isAttackerVoided = false;

    public function __construct(CharacterCacheData $characterCacheData, Voidance $voidance, Ambush $ambush) {
        $this->characterCacheData = $characterCacheData;
        $this->voidance           = $voidance;
        $this->ambush             = $ambush;
    }

    public function setUp(Character $attacker, Character $defender, array $healthObject): array {
        $this->reducePlayerSkills($attacker, $defender);
        $this->handleVoidance($attacker, $defender);

        return $this->handleAmbush($attacker, $defender, $healthObject, $this->voidance->isPlayerVoided());
    }

    public function isAttackerVoided(): bool {
        return $this->isAttackerVoided;
    }

    public function handleAmbush(Character $attacker, Character $defender, array $healthObject, bool $isAttackerVoided): array {
        $healthObject = $this->ambush->attackerAmbushesDefender($attacker, $defender, $isAttackerVoided, $healthObject);

        $this->mergeAttackerMessages($this->ambush->getAttackerMessages());

        $this->mergeDefenderMessages($this->ambush->getDefenderMessages());

        $this->ambush->clearPvpMessage();

        return $healthObject;
    }

    public function handleVoidance(Character $attacker, Character $defender) {
        $this->voidance->pvpVoid($attacker, $defender, $this->characterCacheData);

        $this->mergeAttackerMessages($this->voidance->getAttackerMessages());

        $this->mergeDefenderMessages($this->voidance->getDefenderMessages());

        $this->voidance->clearPvpMessage();

        $this->isAttackerVoided = $this->voidance->isPlayerVoided();
    }

    public function reducePlayerSkills(Character $attacker, Character $defender) {
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
