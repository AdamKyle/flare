<?php
namespace App\Flare\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\CharacterAttackHandler;
use App\Flare\Handlers\MonsterAttackHandler;
use App\Flare\Handlers\SetupFightHandler;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Adventures\Traits\CreateBattleMessages;


class FightService {

    use CreateBattleMessages;

    private $setupFightHandler;

    private $characterInformationBuilder;

    private $characterAttackHandler;

    private $currentMonsterHealth;

    private $currentCharacterHealth;

    private $monsterAttackHandler;

    private $battleLogs = [];

    public function __construct(
        SetupFightHandler $setupFightHandler,
        CharacterInformationBuilder $characterInformationBuilder,
        CharacterAttackHandler $characterAttackHandler,
        MonsterAttackHandler $monsterAttackHandler
    ) {
        $this->setupFightHandler           = $setupFightHandler;
        $this->characterInformationBuilder = $characterInformationBuilder;
        $this->characterAttackHandler      = $characterAttackHandler;
        $this->monsterAttackHandler        = $monsterAttackHandler;
    }

    public function processFight($attacker, $defender, string $attackType) {
        if (!is_null($this->currentCharacterHealth) && !is_null($this->currentMonsterHealth)) {
            if ($this->isCharacterDead()) {
                $this->battleLogs = $this->addMessage(
                    'You have died during the fight! Death has come for you!',
                    'enemy-action-fired',
                    $this->battleLogs
                );

                return false;
            }

            if ($this->isMonsterDead()) {
                $this->battleLogs = $this->addMessage(
                    'The enemy has been slayed!',
                    'action-fired',
                    $this->battleLogs
                );

                return true;
            }
        }


        $this->setupFightHandler->setUpFight($attacker, $defender);

        $newAttackType = $this->setupFightHandler->getAttackType();

        if (!is_null($newAttackType)) {
            $attackType = $newAttackType . $attackType;
        } else {
            $attackType = explode('_', $attackType);
            $attackType = count($attackType) > 1 ? $attackType[1] : $attackType[0];
        }

        $this->battleLogs = [...$this->battleLogs, ...$this->setupFightHandler->getBattleMessages()];

        if ($attacker instanceof Character) {
            $newDefender = $this->setupFightHandler->getModifiedDefender();

            if (!is_null($newDefender)) {
                $defender = $newDefender;
            }
        }

        if (is_null($this->currentCharacterHealth) && is_null($this->currentMonsterHealth)) {
            $characterInformation = $this->characterInformationBuilder->setCharacter($attacker);
            $this->currentCharacterHealth = $characterInformation->buildHealth(!is_null($newAttackType));

            $healthRange = explode('-', $defender->health_range);
            $this->currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + $defender->dur;
        }

        $isCharacterVoided = $newAttackType === 'voided' ? true : false;

        $this->setupFightHandler->reset();

        return $this->fight($attacker, $defender, $attackType, $isCharacterVoided);
    }

    public function fight($attacker, $defender, $attackType, bool $isDefenderVoided = false) {

        if ($attacker instanceof Character) {
            $this->characterAttackHandler->setHealth(
                $this->currentCharacterHealth,
                $this->currentMonsterHealth,
            )->handleAttack($attacker, $defender, $attackType);

            $this->battleLogs             = [...$this->battleLogs, ...$this->characterAttackHandler->getBattleLogs()];
            $this->currentMonsterHealth   = $this->characterAttackHandler->getMonsterHealth();
            $this->currentCharacterHealth = $this->characterAttackHandler->getCharacterHealth();

            $this->characterAttackHandler->resetLogs();
        }

        if ($attacker instanceof Monster) {
            $this->monsterAttackHandler->setHealth($this->currentMonsterHealth, $this->currentCharacterHealth)
                                       ->doAttack($attacker, $defender, $isDefenderVoided);

            $this->battleLogs             = [...$this->battleLogs, ...$this->monsterAttackHandler->getBattleLogs()];

            $this->currentMonsterHealth   = $this->monsterAttackHandler->getMonsterHealth();
            $this->currentCharacterHealth = $this->monsterAttackHandler->getCharacterHealth();

            $this->monsterAttackHandler->resetLogs();
        }

        return $this->processFight($defender, $attacker, $attackType);
    }

    public function getBattleMessages(): array {
        return $this->battleLogs;
    }

    public function reset() {
        $this->currentMonsterHealth   = null;
        $this->currentCharacterHealth = null;
        $this->battleLogs             = [];
    }

    protected function isCharacterDead(): bool {
        return $this->currentCharacterHealth <= 0;
    }

    protected function isMonsterDead(): bool {
        return $this->currentMonsterHealth <= 0;
    }

}
