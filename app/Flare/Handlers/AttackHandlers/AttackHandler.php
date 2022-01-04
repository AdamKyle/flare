<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Builders\CharacterInformationBuilder;
use Cache;
use App\Flare\Builders\CharacterAttackBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;
use App\Game\Adventures\Traits\CreateBattleMessages;

class AttackHandler {

    use CreateBattleMessages;

    private $characterAttackBuilder;

    private $entrancingChanceHandler;

    private $attackExtraActionHandler;

    private $itemHandler;

    private $canHitHandler;

    private $characterHealth;

    private $monsterHealth;

    private $dmgReduction = 0.0;

    private $battleLogs = [];

    public function __construct(
        CharacterAttackBuilder $characterAttackBuilder,
        EntrancingChanceHandler $entrancingChanceHandler,
        AttackExtraActionHandler $attackExtraActionHandler,
        ItemHandler $itemHandler,
        CanHitHandler $canHitHandler,
    ) {
        $this->characterAttackBuilder   = $characterAttackBuilder;
        $this->entrancingChanceHandler  = $entrancingChanceHandler;
        $this->attackExtraActionHandler = $attackExtraActionHandler;
        $this->itemHandler              = $itemHandler;
        $this->canHitHandler            = $canHitHandler;
    }

    public function setCharacterHealth(int $characterHealth): AttackHandler {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): AttackHandler {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function setDmgReduction(float $reduction): AttackHandler {
        $this->dmgReduction = $reduction;

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

        $attackData = $this->getAttackData($attackType, $attacker);
        $voided     = $this->isAttackVoided($attackType);

        if ($this->attackExtraActionHandler->canAutoAttack($characterInfo)) {
            $message = 'You dance along in the shadows, the enemy doesn\'t see you. Strike now!';

            $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

            $this->weaponAttack($characterInfo, $voided);

            $this->useItems($attacker, $defender, $voided);

            return;
        }

        if ($this->entrancingChanceHandler->entrancedEnemy($attacker, $defender, false, $voided)) {

            $this->battleLogs = [...$this->battleLogs, ...$this->entrancingChanceHandler->getBattleLogs()];

            $this->entrancingChanceHandler->resetLogs();

            $this->weaponAttack($characterInfo, $voided);

            $this->useItems($attacker, $defender, $voided);

            return;
        } else {
            $this->battleLogs = [...$this->battleLogs, ...$this->entrancingChanceHandler->getBattleLogs()];
            $this->entrancingChanceHandler->resetLogs();
        }

        if ($this->canHitHandler->canHit($attacker, $defender, $voided)) {
            if ($this->isBlocked($attackData['weapon_damage'], $defender)) {
                $message          = $defender->name . ' Blocked your attack!';
                $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

                $this->useItems($attacker, $defender, $voided);

                return;
            }

            $this->weaponAttack($characterInfo, $voided);

            $this->useItems($attacker, $defender, $voided);

            return;
        }

        $message          = 'You missed with your weapon(s)!';
        $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

        $this->useItems($attacker, $defender, $voided);
    }

    public function weaponAttack(CharacterInformationBuilder $characterInfo, bool $voided) {
        $this->monsterHealth = $this->attackExtraActionHandler->doAttack($characterInfo, $this->monsterHealth, $voided, $this->dmgReduction);

        $this->battleLogs = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

        $this->attackExtraActionHandler->resetMessages();
    }

    protected function isBlocked($damage, $defender): bool {
        return $damage < $defender->ac;
    }

    protected function getAttackData(string $attackType, $attacker): array {
        return Cache::get('character-attack-data-' . $attacker->id)[$attackType];
    }

    protected function isAttackVoided(string $attackType): bool {
        return str_contains($attackType, 'voided');
    }

    protected function useItems($attacker, $defender, bool $voided = false) {
        $itemHandler = $this->itemHandler->setCharacterHealth($this->characterHealth)
                                         ->setMonsterHealth($this->monsterHealth);

        $itemHandler->useItems($attacker, $defender, $voided, $this->dmgReduction);

        $this->characterHealth = $itemHandler->getCharacterHealth();
        $this->monsterHealth   = $itemHandler->getMonsterHealth();
        $this->battleLogs      = [...$this->battleLogs, ...$itemHandler->getBattleMessages()];

        $itemHandler->resetLogs();
    }
}