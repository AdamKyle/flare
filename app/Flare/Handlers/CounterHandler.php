<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\Character\AttackDetails\CharacterTrinketsInformation;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Adventures\Traits\CreateBattleMessages;
use JetBrains\PhpStorm\ArrayShape;

class CounterHandler {

    use CreateBattleMessages;

    private $characterTrinketsInformation;

    private $characterInformationBuilder;

    private $battleMessages = [];

    public function __construct(CharacterTrinketsInformation $characterTrinketsInformation, CharacterInformationBuilder $characterInformationBuilder) {
        $this->characterTrinketsInformation = $characterTrinketsInformation;
        $this->characterInformationBuilder  = $characterInformationBuilder;
    }

    public function getMessages(): array {
        return $this->battleMessages;
    }

    #[ArrayShape(['monster_health' => "float|int", 'character_health' => "float|int"])]
    public function enemyCountersPlayer(Monster|\StdClass $monster, Character $character, int $monsterHealth, int $characterHealth, bool $isCharacterVoided): array {
        if ($this->canEnemyCounter($monster, $character)) {

            $this->battleMessages = $this->addMessage('The enemy manages to counter your attack!', 'enemy-action-fired', $this->battleMessages);

            $damage = $characterHealth * 1000; //round($monster->{$monster->damage_stat} * 0.05);

            $characterHealth -= $damage;

            $this->battleMessages = $this->addMessage('Lashing out the enemy does: ' . number_format($damage), 'enemy-action-fired', $this->battleMessages);

            if ($characterHealth > 0 && $this->canCounterAgain()) {
                $this->battleMessages = $this->addMessage('You manage to counter the enemies counter!', 'action-fired', $this->battleMessages);

                $damage = round($this->characterInformationBuilder->setCharacter($character)->statMod($character->damage_stat) * 0.025);

                if ($isCharacterVoided) {
                    $damage = round($character->{$character->damage_stat} * 0.025);
                }

                $monsterHealth -= $damage;

                $this->battleMessages = $this->addMessage('Countering the enemies counter, you manage to do: ' . number_format($damage), 'action-fired', $this->battleMessages);
            }
        }

        return [
            'monster_health'   => $monsterHealth,
            'character_health' => $characterHealth,
        ];
    }

    #[ArrayShape(['monster_health' => "float|int", 'character_health' => "float|int"])]
    public function playerCountersEnemy(Monster|\StdClass $monster, Character $character, int $monsterHealth, int $characterHealth, bool $isCharacterVoided): array {
        if ($this->canPlayerCounter($character, $monster)) {

            $this->battleMessages = $this->addMessage('You manage to lash out the enemy in a counter move!', 'action-fired', $this->battleMessages);

            $damage = round($this->characterInformationBuilder->setCharacter($character)->statMod($character->damage_stat) * 0.05);

            if ($isCharacterVoided) {
                $damage = round($character->{$character->damage_stat} * 0.05);
            }

            $monsterHealth -= $damage;

            $this->battleMessages = $this->addMessage('Countering the enemy you manage to do: ' . number_format($damage), 'action-fired', $this->battleMessages);

            if ($monsterHealth > 0 && $this->canCounterAgain()) {
                $this->battleMessages = $this->addMessage('The enemy manages to counter your counter!', 'enemy-action-fired', $this->battleMessages);

                $damage = round($monster->{$monster->damage_stat} * 0.025);

                $characterHealth -= $damage;

                $this->battleMessages = $this->addMessage('lashing out the enemy strikes you for: ' . number_format($damage), 'enemy-action-fired', $this->battleMessages);
            }
        }

        return [
            'monster_health'   => $monsterHealth,
            'character_health' => $characterHealth,
        ];
    }

    public function canPlayerCounter(Character $character, Monster|\StdClass $monster): bool {

        $counterChance = $this->characterTrinketsInformation->getCounterChance($character) - $monster->counter_resistance;

        return rand(1, 100) > (100 - 100 * $counterChance);

    }

    public function canEnemyCounter(Monster|\StdClass $monster, Character $character): bool {
        return true;

        $counterChance = $monster->counter_chance - $this->characterTrinketsInformation->getCounterResistanceChance($character);

        return rand(1, 100) > (100 - 100 * $counterChance);
    }

    public function canCounterAgain(): bool {
        return rand(1, 100) > (100 - 100 * 0.02);
    }
}
