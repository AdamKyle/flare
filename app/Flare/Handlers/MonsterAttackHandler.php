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

    private $isMonsterVoided = false;

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

    public function setMonsterVoided(bool $isMonsterVoided): MonsterAttackHandler {
        $this->isMonsterVoided = $isMonsterVoided;

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

        if ($this->entrancingChanceHandler->entrancedEnemy($attacker, $defender, $isDefenderVoided, $this->isMonsterVoided)) {
            $this->battleLogs = $this->entrancingChanceHandler->getBattleLogs();

            $this->entrancingChanceHandler->resetLogs();

            $this->characterHealth -= $monsterAttack;

            $message = $attacker->name . ' hit for: ' . number_format($monsterAttack);
            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired');

            $this->useItems($attacker, $defender, $isDefenderVoided);

            return;
        } else {
            $this->battleLogs = $this->entrancingChanceHandler->getBattleLogs();
        }

        if ($this->canHitHandler->canHit($attacker, $defender, $isDefenderVoided)) {
            if ($this->blockedAttack($monsterAttack, $defender, $isDefenderVoided)) {
                $message          = 'You blocked the enemies attack!';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $this->useItems($attacker, $defender, $isDefenderVoided);

                return;
            }

            $this->characterHealth -= $monsterAttack;

            $message = $attacker->name . ' hit for: ' . number_format($monsterAttack);

            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired');

            $this->useItems($attacker, $defender, $isDefenderVoided);

             return;
        }

        $message          = $attacker->name . ' Missed!';
        $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

        $this->useItems($attacker, $defender, $isDefenderVoided);

        $this->defenderAttmptToHeal($defender, $attacker, $isDefenderVoided);

    }

    protected function defenderAttmptToHeal($defender, $attacker, bool $isDefenderVoided = false) {
        if ($this->characterHealth <= 0) {
            $this->attemptToRessurect($defender, $attacker, $isDefenderVoided);
        } else if (!$isDefenderVoided){
            $this->useLifestealingAffixes($attacker);
        }
    }

    private function attemptToRessurect($defender, $attacker, bool $isDefenderVoided = false) {
        $resChance = $this->characterInformationBuilder->setCharacter($defender)->fetchResurrectionChance();

        $dc = 100 - 100 * $resChance;

        if (rand(1, 100) > $dc) {
            $this->characterHealth = 1;

            $message = 'You are pulled back from the void and given one health!';
            $this->battleLogs = $this->addMessage($message, 'enemy-action-fired', $this->battleLogs);

            if (!$isDefenderVoided) {
                $this->useLifestealingAffixes($attacker);
            }
        }
    }

    private function useLifestealingAffixes($attacker) {
        $handler = $this->itemHandler->setCharacterHealth($this->characterHealth)->setMonsterHealth($this->monsterHealth);

        $canResist  = $this->characterInformation->canAffixesBeResisted();
        $damage     = $this->characterInformation->findLifeStealingAffixes(true);

        $handler->useLifeStealingAffixes($attacker, $damage, $canResist);

        $this->monsterHealth = $handler->getMonsterHealth();
        $this->characterHealth = $handler->getCharacterHealth();

        $this->battleLogs = [...$this->battleLogs, ...$handler->getBattleMessages()];

        $this->itemHandler->resetLogs();
    }

    protected function useItems($attacker, $defender, bool $isDefenderVoided = false) {

        if (!$this->isMonsterVoided) {
            $itemHandler = $this->itemHandler->setCharacterHealth($this->characterHealth)
                ->setMonsterHealth($this->monsterHealth);

            $itemHandler->useArtifacts($attacker, $defender);

            $this->characterHealth = $itemHandler->getCharacterHealth();
            $this->monsterHealth = $itemHandler->getMonsterHealth();

            $this->useAffixes($attacker, $defender);
        }

        if ($this->canHitHandler->canCast($attacker, $defender, $isDefenderVoided)) {
            $itemHandler = $this->itemHandler->setCharacterHealth($this->characterHealth);

            $itemHandler->castSpell($attacker, $defender);

            $this->characterHealth = $itemHandler->getCharacterHealth();

            $this->battleLogs      = [...$this->battleLogs, ...$itemHandler->getBattleMessages()];

            $itemHandler->resetLogs();
        } else {
            $message = 'The enemy fails to cast their damaging spells!';
            $this->battleLogs = $this->addMessage($message, 'info-battle', $this->battleLogs);
        }

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
        if ($attacker->healing_percentage > 0) {
            $defenderReduction = $this->characterInformationBuilder
                ->setCharacter($defender)
                ->getTotalDeduction('healing_reduction');
            $healing            = $attacker->dur * $attacker->max_healing;

            if ($defenderReduction > 0) {
                $message = 'Your rings negate some of the enemies healing power.';
                $this->battleLogs = $this->addMessage($message, 'info-damage', $this->battleLogs);

                $healing -= ceil($healing * $defenderReduction);
            }

            $message = $attacker->name . '\'s healing spells wash over them for: ' . number_format($healing);
            $this->battleLogs = $this->addMessage($message, 'action-fired', $this->battleLogs);

            $this->monsterHealth += $healing;
        }
    }

    protected function blockedAttack(int $monsterAttack, $defender, bool $isDefenderVoided = false): bool {

        $ac = $this->characterInformationBuilder->setCharacter($defender)->buildDefence($isDefenderVoided);

        return $monsterAttack < $ac;
    }
}