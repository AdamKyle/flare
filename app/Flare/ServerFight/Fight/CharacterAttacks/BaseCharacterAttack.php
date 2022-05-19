<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Monster\ServerMonster;

class BaseCharacterAttack {

    private array $battleMessages;

    private int $characterHealth;

    private int $monsterHealth;

    private CharacterAttack $characterAttack;

    public function __construct(CharacterAttack $characterAttack) {

        $this->characterAttack = $characterAttack;

        $this->battleMessages = [];
    }

    public function setCharacterHealth(int $characterHealth): BaseCharacterAttack {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): BaseCharacterAttack {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function doAttack(Character $character, ServerMonster $monster, bool $isPlayerVoided, string $attackType): mixed {
        $response = null;

        switch($attackType) {
            case 'attack':
                $response = $this->characterAttack->attack($character, $monster, $isPlayerVoided, $this->characterHealth, $this->monsterHealth);
                break;
            case 'cast':
                $response = $this->characterAttack->cast($character, $monster, $isPlayerVoided, $this->characterHealth, $this->monsterHealth);
                break;
            default:
                $this->battleMessages[] = ['message' => 'No Attack Type Supplied. Attack Failed for character.', 'event-action'];
        }

        return $response;
    }

    public function getMessages(): array {
        return $this->battleMessages;
    }

    protected function mergeMessages(array $messages) {
        $this->battleMessages = [...$this->battleMessages, ...$messages];
    }

}
