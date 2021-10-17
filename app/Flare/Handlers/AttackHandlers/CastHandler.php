<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Builders\CharacterAttackBuilder;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;
use App\Flare\Handlers\HealingExtraActionHandler;
use App\Game\Adventures\Traits\CreateBattleMessages;

class CastHandler {

    use CreateBattleMessages;

    private $characterAttackBuilder;

    private $entrancingChanceHandler;

    private $attackExtraActionHandler;

    private $healingExtraActionHandler;

    private $itemHandler;

    private $canHitHandler;

    private $characterHealth;

    private $monsterHealth;

    private $battleLogs = [];

    public function __construct(
        CharacterAttackBuilder $characterAttackBuilder,
        EntrancingChanceHandler $entrancingChanceHandler,
        AttackExtraActionHandler $attackExtraActionHandler,
        HealingExtraActionHandler $healingExtraActionHandler,
        ItemHandler $itemHandler,
        CanHitHandler $canHitHandler,
    ) {
        $this->characterAttackBuilder    = $characterAttackBuilder;
        $this->entrancingChanceHandler   = $entrancingChanceHandler;
        $this->attackExtraActionHandler  = $attackExtraActionHandler;
        $this->healingExtraActionHandler = $healingExtraActionHandler;
        $this->itemHandler               = $itemHandler;
        $this->canHitHandler             = $canHitHandler;
    }

    public function setCharacterHealth(int $characterHealth): CastHandler {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): CastHandler {
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

        $attackData = $this->getAttackData($attackType);
        $voided     = $this->isAttackVoided($attackType);

        if ($this->attackExtraActionHandler->canAutoAttack($characterInfo)) {
            $message = 'You dance through out the shadows, weaving a web of deadly magics. The enemy is blind to you. (Auto Hit)';

            $this->battleLogs      = $this->addMessage($message, 'info-damage', $this->battleLogs);

            $this->monsterHealth   = $this->attackExtraActionHandler->castSpells($characterInfo, $this->monsterHealth, $defender, $voided);

            $this->monsterHealth   = $this->attackExtraActionHandler->setCharacterhealth($this->characterHealth)->vampireThirst($characterInfo, $this->monsterHealth, $voided);

            $this->characterHealth = $this->attackExtraActionHandler->getCharacterHealth();

            $this->battleLogs      = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

            $this->attackExtraActionHandler->resetMessages();

            $this->useItems($attacker, $defender, $voided);

            $this->fireOffHealingSpells($characterInfo, $attackData);

            return;
        }

        if ($this->entrancingChanceHandler->entrancedEnemy($attacker, $defender, false, $voided)) {
            $this->monsterHealth   = $this->attackExtraActionHandler->castSpells($characterInfo, $this->monsterHealth, $defender, $voided);

            $this->monsterHealth   = $this->attackExtraActionHandler->setCharacterhealth($this->characterHealth)->vampireThirst($characterInfo, $this->monsterHealth, $voided);

            $this->characterHealth = $this->attackExtraActionHandler->getCharacterHealth();

            $this->battleLogs = [...$this->battleLogs, ...$this->entrancingChanceHandler->getBattleLogs()];
            $this->battleLogs = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

            $this->attackExtraActionHandler->resetMessages();
            $this->entrancingChanceHandler->resetLogs();

            $this->useItems($attacker, $defender, $voided);

            $this->fireOffHealingSpells($characterInfo, $attackData, $voided);

            return;
        } else {
            $this->battleLogs = [...$this->battleLogs, ...$this->entrancingChanceHandler->getBattleLogs()];
            $this->entrancingChanceHandler->resetLogs();
        }

        if ($this->canHitHandler->canCast($attacker, $defender, $voided)) {
            if ($this->isBlocked($attackData['spell_damage'], $defender)) {
                $message          = $defender->name . ' Blocked your damaging spells!';
                $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

                $this->useItems($attacker, $defender, $voided);

                $this->fireOffHealingSpells($characterInfo, $attackData, $voided);

                return;
            }

            $this->monsterHealth   = $this->attackExtraActionHandler->castSpells($characterInfo, $this->monsterHealth, $defender, $voided);

            $this->monsterHealth   = $this->attackExtraActionHandler->setCharacterhealth($this->characterHealth)->vampireThirst($characterInfo, $this->monsterHealth, $voided);

            $this->characterHealth = $this->attackExtraActionHandler->getCharacterHealth();

            $this->battleLogs      = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

            $this->attackExtraActionHandler->resetMessages();

            $this->useItems($attacker, $defender, $voided);

            $this->fireOffHealingSpells($characterInfo, $attackData, $voided);

            return;
        }

        $message          = 'Your spells fizzeled and failed!';
        $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

        $this->useItems($attacker, $defender, $voided);

        $this->fireOffHealingSpells($characterInfo, $attackData, $voided);
    }

    public function fireOffHealingSpells(CharacterInformationBuilder $characterInfo, array $attackData, bool $voided = false) {
        $this->characterHealth = $this->healingExtraActionHandler->healSpells($characterInfo, $this->characterHealth, $attackData);

        $this->battleLogs = [...$this->battleLogs, ...$this->healingExtraActionHandler->getMessages()];

        $this->healingExtraActionHandler->resetMessages();

        $this->fireOffVampireThirst($characterInfo, $voided);
    }

    protected function fireOffVampireThirst(CharacterInformationBuilder $characterInfo, bool $voided = false) {
        $this->monsterHealth   = $this->attackExtraActionHandler->setCharacterhealth($this->characterHealth)->vampireThirst($characterInfo, $this->monsterHealth, $voided);

        $this->characterHealth = $this->attackExtraActionHandler->getCharacterHealth();

        $this->battleLogs      = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

        $this->attackExtraActionHandler->resetMessages();
    }

    protected function isBlocked($damage, $defender): bool {
        return $damage < $defender->ac;
    }

    protected function getAttackData(string $attackType): array {
        if ($this->isAttackVoided($attackType)) {
            return $this->characterAttackBuilder->buildAttack(true);
        }

        return $this->characterAttackBuilder->buildAttack();
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