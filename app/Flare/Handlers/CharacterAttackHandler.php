<?php

namespace App\Flare\Handlers;

use App\Flare\Handlers\AttackHandlers\AttackHandler;
use App\Flare\Values\AttackTypeValue;

class CharacterAttackHandler {

    private $attackHandler;

    private $battleLogs = [];

    private $characterCurrentHealth;

    private $monsterCurrentHealth;

    public function __construct(AttackHandler $attackHandler) {
        $this->attackHandler = $attackHandler;
    }

    public function setHealth(int $characterCurrentHealth, int $monsterCurrentHealth): CharacterAttackHandler {
        $this->characterCurrentHealth = $characterCurrentHealth;
        $this->monsterCurrentHealth   = $monsterCurrentHealth;


        return $this;
    }

    public function getMonsterHealth(): int {
        return $this->monsterCurrentHealth;
    }

    public function getCharacterHealth(): int {
        return $this->characterCurrentHealth;
    }

    public function getBattleLogs(): array {
        return $this->battleLogs;
    }

    public function resetLogs() {
        $this->battleLogs = [];
    }

    public function handleAttack($attacker, $defender, string $attackType) {
        switch ($attackType) {
            case AttackTypeValue::ATTACK:
            case AttackTypeValue::VOIDED_ATTACK:
                $this->handleWeaponAttack($attacker, $defender, $attackType);
                break;
            case AttackTypeValue::CAST:
            case AttackTypeValue::VOIDED_CAST:
                break;
            case AttackTypeValue::CAST_AND_ATTACK:
            case AttackTypeValue::VOIDED_CAST_AND_ATTACK:
                break;
            case AttackTypeValue::ATTACK_AND_CAST:
            case AttackTypeValue::VOIDED_ATTACK_AND_CAST:
                break;
            case AttackTypeValue::DEFEND:
            case AttackTypeValue::VOIDED_DEFEND:
                break;
            default:
                throw new \Exception('Unexpected value');
        }
    }

    protected function handleWeaponAttack($attacker, $defender, string $attackType) {
        $this->attackHandler->setMonsterHealth($this->monsterCurrentHealth)
                            ->setCharacterHealth($this->characterCurrentHealth)
                            ->doAttack($attacker, $defender, $attackType);

        $this->monsterCurrentHealth   = $this->attackHandler->getMonsterHealth();
        $this->characterCurrentHealth = $this->attackHandler->getCharacterHealth();
        $this->battleLogs             = $this->attackHandler->getBattleMessages();

        $this->attackHandler->resetLogs();
    }
}