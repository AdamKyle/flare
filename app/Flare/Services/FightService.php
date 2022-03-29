<?php
namespace App\Flare\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\AmbushHandler;
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

    private $ambushHandler;

    private $isMonsterVoided = false;

    private $battleLogs = [];

    private $attackOnce = false;

    private $fightSetUp = false;

    private $tookTooLongCounter = 0;

    private $chrDmgReduction    = 0.0;

    private $fightTookTooLong = false;

    private $newAttackType = null;


    public function __construct(
        SetupFightHandler $setupFightHandler,
        CharacterInformationBuilder $characterInformationBuilder,
        CharacterAttackHandler $characterAttackHandler,
        MonsterAttackHandler $monsterAttackHandler,
        AmbushHandler $ambushHandler,
    ) {
        $this->setupFightHandler           = $setupFightHandler;
        $this->characterInformationBuilder = $characterInformationBuilder;
        $this->characterAttackHandler      = $characterAttackHandler;
        $this->monsterAttackHandler        = $monsterAttackHandler;
        $this->ambushHandler               = $ambushHandler;
    }

    public function setAttackTimes(bool $attackOnce): FightService {
        $this->attackOnce = $attackOnce;

        return $this;
    }

    public function setCelestialFightHealth(int $currentHealth): FightService {
        $this->currentMonsterHealth = $currentHealth;

        return $this;
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
                    'The enemy has been defeated!',
                    'action-fired',
                    $this->battleLogs
                );

                return true;
            }
        }

        if (!$this->fightSetUp) {
            $this->setupFightHandler->setUpFight($attacker, $defender);

            $this->newAttackType = $this->setupFightHandler->getAttackType();

            $this->isMonsterVoided = $this->setupFightHandler->getIsMonsterVoided();

            $this->chrDmgReduction = $this->setupFightHandler->getCharacterDamageReduction();

            if (!is_null($this->newAttackType) && strpos($this->newAttackType, $attackType) === false) {
                $attackType = $this->newAttackType . $attackType;
            } else if (is_null($this->newAttackType)) {
                $attackType = str_replace('voided_', '', $attackType);
            }

            $this->battleLogs = [...$this->battleLogs, ...$this->setupFightHandler->getBattleMessages()];

            $this->fightSetUp = true;
        }

        if ($attacker instanceof Character) {
            $defender = $this->setupFightHandler->getModifiedDefender();
        }

        $isCharacterVoided = $this->newAttackType === 'voided_';

        if (is_null($this->currentCharacterHealth)) {
            $characterInformation         = $this->characterInformationBuilder->setCharacter($attacker);
            $this->currentCharacterHealth = $characterInformation->buildHealth($isCharacterVoided);
        }

        if (!$this->attackOnce && ($defender instanceof  Monster || $defender instanceof  \StdClass)) {
            $healthRange                = explode('-', $defender->health_range);
            $this->currentMonsterHealth = rand($healthRange[0], $healthRange[1]);
        }

        $healthObject = $this->ambushHandler->ambush($defender, $attacker, $this->currentMonsterHealth, $this->currentCharacterHealth, $isCharacterVoided);

        $this->battleLogs = [...$this->battleLogs, ...$this->ambushHandler->getMessages()];

        $this->ambushHandler->clearMessages();

        if ($healthObject['monster_health'] <= 0 ) {
            $this->battleLogs = $this->addMessage('The enemy has been slaughtered by your ambush!', 'enemy-action-fired', $this->battleLogs);

            $this->currentMonsterHealth = $healthObject['monster_health'];

            return true;
        }

        if ($healthObject['character_health'] <= 0) {
            $this->battleLogs = $this->addMessage('You have been slaughtered by the ambush!', 'enemy-action-fired', $this->battleLogs);

            $this->currentCharacterHealth = $healthObject['character_health'];

            return false;
        }

        return $this->fight($attacker, $defender, $attackType, $isCharacterVoided);
    }

    public function fight($attacker, $defender, $attackType, bool $isDefenderVoided = false) {

        if ($attacker instanceof Character) {
            $this->characterAttackHandler->setHealth(
                $this->currentCharacterHealth,
                $this->currentMonsterHealth,
            )->handleAttack($attacker, $defender, $attackType, $this->chrDmgReduction);

            $this->battleLogs             = [...$this->battleLogs, ...$this->characterAttackHandler->getBattleLogs()];
            $this->currentMonsterHealth   = $this->characterAttackHandler->getMonsterHealth();
            $this->currentCharacterHealth = $this->characterAttackHandler->getCharacterHealth();

            $this->characterAttackHandler->resetLogs();
        }

        if ($attacker instanceof Monster) {

            $this->monsterAttackHandler->setHealth($this->currentMonsterHealth, $this->currentCharacterHealth)
                                       ->setMonsterVoided($this->isMonsterVoided)
                                       ->doAttack($attacker, $defender, $attackType, $isDefenderVoided);

            $this->battleLogs             = [...$this->battleLogs, ...$this->monsterAttackHandler->getBattleLogs()];

            $this->currentMonsterHealth   = $this->monsterAttackHandler->getMonsterHealth();
            $this->currentCharacterHealth = $this->monsterAttackHandler->getCharacterHealth();

            $this->monsterAttackHandler->resetLogs();

            if ($this->attackOnce) {
                return;
            }
        }

        if (!$this->fightTookTooLong) {
            $this->tookTooLongCounter++;
        }

        if ($this->tookTooLongCounter >= 10) {
            $this->fightTookTooLong = true;
            return;
        }

        return $this->processFight($defender, $attacker, $attackType);
    }

    public function getBattleMessages(): array {
        return $this->battleLogs;
    }

    public function getCharacterHealth(): int {
        return $this->currentCharacterHealth;
    }

    public function getMonsterHealth(): int {
        return $this->currentMonsterHealth;
    }

    public function tookTooLong(): bool {
        return $this->fightTookTooLong;
    }

    public function reset() {
        $this->currentMonsterHealth   = null;
        $this->currentCharacterHealth = null;
        $this->battleLogs             = [];
        $this->tookTooLongCounter     = 0;
        $this->fightTookTooLong       = false;
        $this->fightSetUp             = false;
        $this->newAttackType          = null;

        $this->setupFightHandler->reset();
    }

    protected function isCharacterDead(): bool {
        return $this->currentCharacterHealth <= 0;
    }

    protected function isMonsterDead(): bool {
        return $this->currentMonsterHealth <= 0;
    }

}
