<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Builders\Character\AttackDetails\CharacterAttackBuilder;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;
use App\Game\Adventures\Traits\CreateBattleMessages;

class DefendHandler {

    use CreateBattleMessages;

    private $characterAttackBuilder;

    private $entrancingChanceHandler;

    private $attackExtraActionHandler;

    private $itemHandler;

    private $characterHealth;

    private $monsterHealth;

    private $dmgReduction = 0.0;

    private $battleLogs = [];

    public function __construct(
        CharacterAttackBuilder $characterAttackBuilder,
        EntrancingChanceHandler $entrancingChanceHandler,
        AttackExtraActionHandler $attackExtraActionHandler,
        ItemHandler $itemHandler,
    ) {
        $this->characterAttackBuilder    = $characterAttackBuilder;
        $this->entrancingChanceHandler   = $entrancingChanceHandler;
        $this->attackExtraActionHandler  = $attackExtraActionHandler;
        $this->itemHandler               = $itemHandler;
    }

    public function setCharacterHealth(int $characterHealth): DefendHandler {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setDmgReduction(float $reduction): DefendHandler {
        $this->dmgReduction = $reduction;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): DefendHandler {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function getCharacterHealth(): int {
        return $this->characterHealth;
    }

    public function getMonsterHealth(): int {
        return $this->monsterHealth;
    }

    public function getBattleMessages(): array {
        return $this->battleLogs;
    }

    public function resetLogs() {
        $this->battleLogs = [];
    }

    public function doAttack($attacker, $defender, string $attackType) {

        $this->characterAttackBuilder = $this->characterAttackBuilder->setCharacter($attacker);
        $characterInfo                = $this->characterAttackBuilder->getInformationBuilder()->setCharacter($attacker);

        $voided     = $this->isAttackVoided($attackType);

        if ($this->attackExtraActionHandler->canAutoAttack($characterInfo)) {
            $message          = 'You dance through out the shadows, weaving a web of deadly magics. The enemy is blind to you. (Auto Hit)';

            $this->battleLogs = $this->addMessage($message, 'action-fired', $this->battleLogs);

            $this->useItems($attacker, $defender, $voided);

            $this->fireOffVampireThirst($characterInfo, $voided);

            return;
        }

        if ($this->entrancingChanceHandler->entrancedEnemy($attacker, $defender, false, $voided)) {
            $this->battleLogs = [...$this->battleLogs, ...$this->entrancingChanceHandler->getBattleLogs()];

            $this->useItems($attacker, $defender, $voided);

            $this->fireOffVampireThirst($characterInfo, $voided);

            return;
        } else {
            $this->battleLogs = [...$this->battleLogs, ...$this->entrancingChanceHandler->getBattleLogs()];
            $this->entrancingChanceHandler->resetLogs();
        }


        $this->useItems($attacker, $defender, $voided);

        $this->fireOffVampireThirst($characterInfo, $voided);
    }

    protected function fireOffVampireThirst(CharacterInformationBuilder $characterInfo, bool $voided = false) {
        $this->monsterHealth   = $this->attackExtraActionHandler->setCharacterhealth($this->characterHealth)->vampireThirst($characterInfo, $this->monsterHealth, $voided);

        $this->characterHealth = $this->attackExtraActionHandler->getCharacterHealth();

        $this->battleLogs      = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

        $this->attackExtraActionHandler->resetMessages();
    }


    protected function isAttackVoided(string $attackType): bool {
        return str_contains($attackType, 'voided');
    }

    protected function useItems($attacker, $defender, bool $voided = false) {
        $itemHandler = $this->itemHandler->setCharacterHealth($this->characterHealth)
            ->setMonsterHealth($this->monsterHealth);

        $itemHandler->useItems($attacker, $defender, $voided);

        $this->characterHealth = $itemHandler->getCharacterHealth();
        $this->monsterHealth   = $itemHandler->getMonsterHealth();
        $this->battleLogs      = [...$this->battleLogs, ...$itemHandler->getBattleMessages()];

        $itemHandler->resetLogs();
    }
}
