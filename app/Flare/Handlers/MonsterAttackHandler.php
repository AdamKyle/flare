<?php

namespace App\Flare\Handlers;


use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\AttackHandlers\CanHitHandler;
use App\Flare\Handlers\AttackHandlers\EntrancingChanceHandler;
use App\Flare\Handlers\AttackHandlers\ItemHandler;
use App\Game\Adventures\Traits\CreateBattleMessages;

class MonsterAttackHandler {

    use CreateBattleMessages;

    private $characterInformationBuilder;

    private $entrancingChanceHandler;

    private $itemHandler;

    private $canHitHandler;

    private $characterHealth;

    private $monsterHealth;

    private $battleLogs = [];

    public function __construct(
        CharacterInformationBuilder $characterInformationBuilder,
        EntrancingChanceHandler $entrancingChanceHandler,
        ItemHandler $itemHandler,
        CanHitHandler $canHitHandler,
    ) {
        $this->characterInformationBuilder = $characterInformationBuilder;
        $this->entrancingChanceHandler     = $entrancingChanceHandler;
        $this->canHitHandler               = $canHitHandler;
        $this->itemHandler                 = $itemHandler;
    }

    public function setHealth(int $monsterHealth, int $characterHealth): MonsterAttackHandler {
        $this->monsterHealth   = $monsterHealth;
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function getMonsterHealth(): int {
        return $this->monsterHealth;
    }

    public function getCharacterHealth(): int {
        return $this->characterHealth;
    }

    public function getBattleLogs(): array {
        return $this->battleLogs;
    }

    public function resetLogs() {
        $this->battleLogs = [];
    }

    public function doAttack($attacker, $defender, bool $isDefenderVoided = false) {
        $monsterAttack = explode('-', $attacker->attack_range);
        $monsterAttack = rand($monsterAttack[0], $monsterAttack[1]);

        if ($this->entrancingChanceHandler->entrancedEnemy($attacker, $defender, $isDefenderVoided)) {
            $this->battleLogs = $this->entrancingChanceHandler->getBattleLogs();

            $this->entrancingChanceHandler->resetLogs();

            $this->characterHealth -= $monsterAttack;

            $message = $attacker->name . ' hit for: ' . number_format($monsterAttack);
            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired');

            $this->useItems($attacker, $defender);

            return;
        } else {
            $this->battleLogs = $this->entrancingChanceHandler->getBattleLogs();
        }

        if ($this->canHitHandler->canHit($attacker, $defender, $isDefenderVoided)) {
            if ($this->blockedAttack($monsterAttack, $defender, $isDefenderVoided)) {
                $message          = 'You blocked the enemies attack!';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $this->useItems($attacker, $defender);

                return;
            }

            $message = $attacker->name . ' hit for: ' . number_format($monsterAttack);
            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired');

            $this->useItems($attacker, $defender);

            return;
        }

        $message          = $attacker->name . ' Missed!';
        $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

        $this->useItems($attacker, $defender);
    }

    protected function useItems($attacker, $defender) {
        $itemHandler = $this->itemHandler->setCharacterHealth($this->characterHealth)
                                         ->setMonsterHealth($this->monsterHealth);

        $itemHandler->useArtifacts($attacker, $defender);

        $this->characterHealth = $itemHandler->getCharacterHealth();
        $this->monsterHealth   = $itemHandler->getMonsterHealth();
        $this->battleLogs      = [...$this->battleLogs, ...$itemHandler->getBattleMessages()];

        $itemHandler->resetLogs();

        $this->useAffixes($attacker, $defender);

        $itemHandler = $this->itemHandler->setCharacterHealth($this->characterHealth);

        $itemHandler->castSpell($attacker, $defender);

        $this->characterHealth = $itemHandler->getCharacterHealth();
        $this->battleLogs      = [...$this->battleLogs, ...$itemHandler->getBattleMessages()];

        $itemHandler->resetLogs();

        $this->heal($attacker, $defender);
    }

    protected function useAffixes($attacker, $defender) {
        if ($attacker->max_affix_damage > 0) {
            $defenderReduction = $this->characterInformationBuilder
                                      ->setCharacter($defender)
                                      ->getTotalDeduction('affix_damage_reduction');
            $damage            = rand(1, $attacker->max_affix_damage);

            if ($defenderReduction > 0) {
                $message = 'Your rings negate some of the enemies enchantment damage.';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $damage -= ceil($damage * $defenderReduction);
            }

            $message = $attacker->name . '\'s enchantments glow, lashing out for: ' . number_format($damage);
            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

            $this->characterHealth -= $damage;
        }
    }

    protected function heal($attacker, $defender) {
        if ($attacker->max_healing > 0) {
            $defenderReduction = $this->characterInformationBuilder
                ->setCharacter($defender)
                ->getTotalDeduction('healing_reduction');
            $healing            = $attacker->dur * $attacker->max_healing;

            if ($defenderReduction > 0) {
                $message = 'Your rings negate some of the enemies healing power.';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $healing -= ceil($healing * $defenderReduction);
            }

            $message = $attacker->name + '\'s healing spells wash over them for: ' . number_format($healing);
            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

            $this->monsterHealth += $healing;
        }
    }

    protected function blockedAttack(int $monsterAttack, $defender, bool $isDefenderVoided = false): bool {

        $ac = $this->characterInformationBuilder->setCharacter($defender)->buildDefence($isDefenderVoided);

        return $monsterAttack < $ac;
    }
}