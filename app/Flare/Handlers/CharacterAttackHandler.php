<?php

namespace App\Flare\Handlers;

use App\Flare\Handlers\AttackHandlers\AttackHandler;
use App\Flare\Handlers\AttackHandlers\CastHandler;
use App\Flare\Handlers\AttackHandlers\DefendHandler;
use App\Flare\Values\AttackTypeValue;

class CharacterAttackHandler {

    private $attackHandler;

    private $castHandler;

    private $battleLogs = [];

    private $characterCurrentHealth;

    private $monsterCurrentHealth;

    public function __construct(AttackHandler $attackHandler, CastHandler $castHandler, DefendHandler $defendHandler) {
        $this->attackHandler = $attackHandler;
        $this->castHandler   = $castHandler;
        $this->defendHandler = $defendHandler;
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
                $this->handleCastAttack($attacker, $defender, $attackType);
                break;
            case AttackTypeValue::CAST_AND_ATTACK:
            case AttackTypeValue::VOIDED_CAST_AND_ATTACK:
                break;
            case AttackTypeValue::ATTACK_AND_CAST:
            case AttackTypeValue::VOIDED_ATTACK_AND_CAST:
                break;
            case AttackTypeValue::DEFEND:
            case AttackTypeValue::VOIDED_DEFEND:
                $this->handleDefend($attacker, $defender, $attackType);
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

    protected function handleCastAttack($attacker, $defender, string $attackType) {
        $this->castHandler->setMonsterHealth($this->monsterCurrentHealth)
            ->setCharacterHealth($this->characterCurrentHealth)
            ->doAttack($attacker, $defender, $attackType);

        $this->monsterCurrentHealth   = $this->castHandler->getMonsterHealth();
        $this->characterCurrentHealth = $this->castHandler->getCharacterHealth();
        $this->battleLogs             = $this->castHandler->getBattleMessages();

        $this->castHandler->resetLogs();
    }

    protected function handleDefend($attacker, $defender, string $attackType) {
        $this->defendHandler->setMonsterHealth($this->monsterCurrentHealth)
            ->setCharacterHealth($this->characterCurrentHealth)
            ->doAttack($attacker, $defender, $attackType);

        $this->monsterCurrentHealth   = $this->defendHandler->getMonsterHealth();
        $this->characterCurrentHealth = $this->defendHandler->getCharacterHealth();
        $this->battleLogs             = $this->defendHandler->getBattleMessages();

        $this->defendHandler->resetLogs();
    }
}