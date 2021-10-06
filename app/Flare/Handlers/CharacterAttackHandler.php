<?php

namespace App\Flare\Handlers;

use App\Flare\Values\AttackTypeValue;

class CharacterAttackHandler {

    private $battleLogs = [];

    private $characterCurrentHealth;

    private $monsterCurrentHealth;

    public function setHealth(int $characterCurrentHealth, int $monsterCurrentHealth): CharacterAttackHandler {
        $this->characterCurrentHealth = $characterCurrentHealth;
        $this->monsterCurrentHealth   = $monsterCurrentHealth;


        return $this;
    }

    public function handleAttack($attacker, $defender, $attackType) {
        switch ($attacker) {
            case AttackTypeValue::ATTACK:
            case AttackTypeValue::VOIDED_ATTACK:
                break;
            case AttackTypeValue::CAST:
            case AttackTypeValue::VOIDED_CAST:
                break;
            case AttackTypeValue::CAST_AND_ATTACK:
            case AttackTypeValue::VOIDED_CAST_AND_ATTACK:
                break;
            case AttackTypeValue::ATTACK_AND_CAST:
            case AttackTypeValue::ATTACK_AND_CAST:
                break;
            case AttackTypeValue::DEFEND:
            case AttackTypeValue::VOIDED_DEFEND:
                break;
        }
    }
}