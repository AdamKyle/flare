<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\BaseCharacterAttack;
use App\Flare\ServerFight\Monster\ServerMonster;

class Attack {

    private bool $isCharacterVoided;

    private int $characterHealth;

    private int $monsterHealth;

    private int $attackCounter;

    private array $battleMessages;

    private bool $tookTooLong;

    private BaseCharacterAttack $baseCharacterAttack;

    private MonsterAttack $monsterAttack;

    public function __construct(BaseCharacterAttack $baseCharacterAttack, MonsterAttack $monsterAttack) {

        $this->baseCharacterAttack = $baseCharacterAttack;
        $this->monsterAttack       = $monsterAttack;

        $this->battleMessages = [];
    }

    public function setIsCharacterVoided(bool $isVoided): Attack {
        $this->isCharacterVoided = $isVoided;
        $this->attackCounter     = 0;

        return $this;
    }

    public function setHealth(array $healthObject): Attack {
        $this->characterHealth = $healthObject['character_health'];
        $this->monsterHealth   = $healthObject['monster_health'];

        return $this;
    }

    public function mergeBattleMessages(array $messages) {
        $this->battleMessages = [...$this->battleMessages, ...$messages];
    }

    public function resetBattleMessages() {
        $this->battleMessages = [];
    }

    public function tookTooLong(): bool {
        return $this->tookTooLong;
    }

    public function getMessages(): array {
        return $this->battleMessages;
    }

    public function getCharacterHealth() {
        return $this->characterHealth;
    }

    public function getMonsterHealth() {
        return $this->monsterHealth;
    }

    public function attack(Character $character, ServerMonster $serverMonster, string $attackType, string $whoAttacks) {

        if ($this->characterHealth <= 0) {
            $this->battleMessages[] = ['message' => 'You must resurrect first!', 'type' => 'enemy-action'];

            return;
        }

        if ($this->monsterHealth <= 0) {
            $this->battleMessages[] = ['message' => $serverMonster->getName() . ' has been defeated!', 'type' => 'enemy-action'];

            return;
        }

        if ($this->attackCounter >= 10) {
            $this->battleMessages[] = ['message' => 'Something is wrong. You attack took way too long. You seem evenly matched, try buying better gear or crafting it.', 'type' => 'enemy-action'];

            $this->tookTooLong = true;

            return;
        }

        if ($whoAttacks === 'character') {
            $response = $this->baseCharacterAttack->setMonsterHealth($this->monsterHealth)
                                                  ->setCharacterHealth($this->characterHealth)
                                                  ->doAttack($character, $serverMonster, $this->isCharacterVoided, $attackType);


            if (is_null($response)) {
                $this->mergeBattleMessages($this->baseCharacterAttack->getMessages());

                return;
            }

            $this->mergeBattleMessages($response->getMessages());

            $this->characterHealth = $response->getCharacterHealth();
            $this->monsterHealth   = $response->getMonsterHealth();

            $response->resetMessages();

            $this->attackCounter++;

            $this->attack($character, $serverMonster, $attackType, 'monster');
        }

        if ($whoAttacks === 'monster') {
            $this->monsterAttack->setIsCharacterVoided($this->isCharacterVoided)
                                ->setCharacterHealth($this->characterHealth)
                                ->setMonsterHealth($this->monsterHealth)
                                ->monsterAttack($serverMonster, $character, $attackType);

            $this->mergeBattleMessages($this->monsterAttack->getMessages());

            $this->characterHealth = $this->monsterAttack->getCharacterHealth();
            $this->monsterHealth   = $this->getMonsterHealth();

            if ($this->monsterHealth > $serverMonster->getHealth()) {
                $this->monsterHealth = $serverMonster->getHealth();
            }

            $this->monsterAttack->clearMessages();

            $this->attackCounter++;

            $this->attack($character, $serverMonster, $attackType, 'monster');
        }
    }
}
