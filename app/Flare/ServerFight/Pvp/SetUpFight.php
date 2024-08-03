<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\Ambush;
use App\Flare\ServerFight\Fight\Voidance;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class SetUpFight extends PvpMessages
{
    private CharacterCacheData $characterCacheData;

    private Voidance $voidance;

    private Ambush $ambush;

    const ATTACKER = 'attacker';

    const DEFENDER = 'defender';

    private $isAttackerVoided = false;

    private $isEnemyVoided = false;

    public function __construct(CharacterCacheData $characterCacheData, Voidance $voidance, Ambush $ambush)
    {
        $this->characterCacheData = $characterCacheData;
        $this->voidance = $voidance;
        $this->ambush = $ambush;
    }

    public function setUp(Character $attacker, Character $defender, array $healthObject): array
    {
        $this->reducePlayerSkills($attacker, $defender);
        $this->reduceResistances($attacker, $defender);
        $this->handleVoidance($attacker, $defender);

        return $this->handleAmbush($attacker, $defender, $healthObject, $this->voidance->isPlayerVoided());
    }

    public function isAttackerVoided(): bool
    {
        return $this->isAttackerVoided;
    }

    public function isEnemyVoided(): bool
    {
        return $this->isEnemyVoided;
    }

    public function handleAmbush(Character $attacker, Character $defender, array $healthObject, bool $isAttackerVoided): array
    {
        $healthObject = $this->ambush->attackerAmbushesDefender($attacker, $defender, $isAttackerVoided, $healthObject, true);

        $this->mergeAttackerMessages($this->ambush->getAttackerMessages());

        $this->mergeDefenderMessages($this->ambush->getDefenderMessages());

        $this->ambush->clearPvpMessage();

        return $healthObject;
    }

    public function handleVoidance(Character $attacker, Character $defender)
    {
        $this->voidance->pvpVoid($attacker, $defender, $this->characterCacheData);

        $this->mergeAttackerMessages($this->voidance->getAttackerMessages());

        $this->mergeDefenderMessages($this->voidance->getDefenderMessages());

        $this->voidance->clearPvpMessage();

        $this->isAttackerVoided = $this->voidance->isPlayerVoided();

        $this->isEnemyVoided = $this->voidance->isEnemyVoided();
    }

    public function reducePlayerSkills(Character $attacker, Character $defender)
    {
        $attackerResult = $this->reduceSkills($attacker, $defender);
        $defenderResult = $this->reduceSkills($defender, $attacker);

        if ($attackerResult) {
            $this->addAttackerMessage('You caused the enemy to thrash around like a lunatic. Skills reduced!', 'player-action');
            $this->addDefenderMessage($attacker->name.' causes you to thrash around blindly. (Core skills reduced!)', 'enemy-action');
        }

        if ($defenderResult) {
            $this->addDefenderMessage('You caused the enemy to thrash around like a lunatic. Skills reduced!', 'player-action');
            $this->addAttackerMessage($defender->name.' causes you to thrash around blindly. (Core skills reduced!)', 'enemy-action');
        }
    }

    public function reduceCharacterResistances(Character $attacker, Character $defender)
    {
        $attackerResult = $this->reduceResistances($attacker, $defender);
        $defenderResult = $this->reduceResistances($defender, $attacker);

        if ($attackerResult) {
            $this->addAttackerMessage('You make the enemy shudder in fear. Resistances reduced!', 'player-action');
            $this->addDefenderMessage($attacker->name.' causes you to cry out in agony (Core resistances reduced!)', 'enemy-action');
        }

        if ($defenderResult) {
            $this->addDefenderMessage('You make the enemy shudder in fear. Resistances reduced!', 'player-action');
            $this->addAttackerMessage($defender->name.' causes you to cry out in agony (Core resistances reduced!)', 'enemy-action');
        }
    }

    protected function reduceResistances(Character $attacker, Character $defender)
    {
        $attackerResistanceReduction = $this->characterCacheData->getCachedCharacterData($attacker, 'resistance_reduction');

        if ($attackerResistanceReduction > 0.0) {
            $defenderCache = $this->characterCacheData->getCharacterSheetCache($defender);

            foreach ($defenderCache as $attributeName => $value) {
                switch ($attributeName) {
                    case 'devouring_light_res':
                    case 'devouring_darkness_res':
                    case 'ambush_resistance':
                    case 'counter_resistance':
                    case 'spell_evasion':
                    case 'affix_damage_reduction':
                    case 'healing_reduction':
                        $defenderCache[$attributeName] = $this->adjustValue($value, $attackerResistanceReduction);
                    default:
                }
            }

            return true;
        }

        return false;
    }

    protected function adjustValue(float $value, float $reduction): float
    {
        $newValue = $value - $reduction;

        if ($newValue < 0.0) {
            return 0.0;
        }

        return $newValue;
    }

    protected function reduceSkills(Character $attacker, Character $defender)
    {
        $skillReduction = $this->characterCacheData->getCachedCharacterData($attacker, 'skill_reduction');

        if ($skillReduction > 0.0) {

            $defenderCache = $this->characterCacheData->getCharacterSheetCache($defender);

            foreach ($defenderCache['skills'] as $skillName => $value) {
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
