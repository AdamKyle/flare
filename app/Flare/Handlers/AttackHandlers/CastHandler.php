<?php

namespace App\Flare\Handlers\AttackHandlers;

use Cache;
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

    private $dmgReduction = 0.0;

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

    public function setDmgReduction(float $reduction): CastHandler {
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
        $voided                       = $this->isAttackVoided($attackType);
        $totalSpellDamage             = $characterInfo->getTotalSpellDamage($voided);
        $totalHealing                 = $characterInfo->buildHealFor($voided);
        $canHit                       = $this->canHitHandler->canCast($attacker, $defender, $voided);

        if ($this->attackExtraActionHandler->canAutoAttack($characterInfo)) {
            $this->castDamageSpells($characterInfo, $defender, $voided);
            $this->fireOffHealingSpells($characterInfo, $voided);
            $this->useItems($attacker, $defender, $voided);

            return;
        }

        if ($this->entrancingChanceHandler->entrancedEnemy($attacker, $defender, $voided)) {
            $this->castDamageSpells($characterInfo, $defender, $voided);
            $this->fireOffHealingSpells($characterInfo, $voided);
            $this->useItems($attacker, $defender, $voided);

            return;
        }

        if ($totalSpellDamage > 0) {
            if ($canHit) {
                if (!$this->isBlocked($totalSpellDamage, $defender)) {
                    $this->castDamageSpells($characterInfo, $defender, $voided);
                } else {
                    $message          = $defender->name . ' Blocked your damaging spell!';
                    $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);
                }
            } else {
                $message          = 'Your damage spell missed!';
                $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);
            }
        }

        if ($totalHealing > 0) {
            $this->fireOffHealingSpells($characterInfo, $voided);
        }

        $this->useItems($attacker, $defender, $voided);
    }

    public function fireOffHealingSpells(CharacterInformationBuilder $characterInfo, int $characterHealth = null, bool $voided = false) {
        if (!is_null($characterHealth)) {

            $health = $this->healingExtraActionHandler->healSpells($characterInfo, $characterHealth, $voided);

            $this->battleLogs = [...$this->battleLogs, ...$this->healingExtraActionHandler->getMessages()];

            $this->healingExtraActionHandler->resetMessages();

            return $this->fireOffVampireThirst($characterInfo, $health, $voided);
        }

        $this->characterHealth = $this->healingExtraActionHandler->healSpells($characterInfo, $this->characterHealth, $voided);

        $this->battleLogs = [...$this->battleLogs, ...$this->healingExtraActionHandler->getMessages()];

        $this->healingExtraActionHandler->resetMessages();

        $this->fireOffVampireThirst($characterInfo, $voided);
    }

    public function castDamageSpells(CharacterInformationBuilder $characterInfo, $defender, bool $voided = false) {
        $this->monsterHealth   = $this->attackExtraActionHandler->castSpells($characterInfo, $defender, $this->monsterHealth, $voided);
        $this->monsterHealth   = $this->attackExtraActionHandler->setCharacterhealth($this->characterHealth)->vampireThirst($characterInfo, $this->monsterHealth, $voided);
        $this->characterHealth = $this->attackExtraActionHandler->getCharacterHealth();

        $this->battleLogs      = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

        $this->attackExtraActionHandler->resetMessages();
    }

    protected function fireOffVampireThirst(CharacterInformationBuilder $characterInfo, int $characterHealth = null, bool $voided = false) {
        $health = is_null($characterHealth) ? $this->characterHealth : $characterHealth;

        $this->monsterHealth   = $this->attackExtraActionHandler->setCharacterhealth($health)->vampireThirst($characterInfo, $this->monsterHealth, $voided);

        if (!is_null($characterHealth)) {
            $health           = $this->attackExtraActionHandler->getCharacterHealth();

            $this->battleLogs = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

            return $health;
        }

        $this->characterHealth = $this->attackExtraActionHandler->getCharacterHealth();

        $this->battleLogs      = [...$this->battleLogs, ...$this->attackExtraActionHandler->getMessages()];

        $this->attackExtraActionHandler->resetMessages();

        return $this->characterHealth;
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

        $itemHandler->useItems($attacker, $defender, $voided);

        $this->characterHealth = $itemHandler->getCharacterHealth();
        $this->monsterHealth   = $itemHandler->getMonsterHealth();
        $this->battleLogs      = [...$this->battleLogs, ...$itemHandler->getBattleMessages()];

        $itemHandler->resetLogs();
    }
}