<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Adventures\Traits\CreateBattleMessages;

class AmbushHandler {

    use CreateBattleMessages;

    private $battleLogs            = [];

    private $characterInformationBuilder;

    public function __construct(CharacterInformationBuilder $characterInformationBuilder) {
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    public function getMessages(): array {
        return $this->battleLogs;
    }

    public function clearMessages(): void {
        $this->battleLogs = [];
    }

    public function ambush(Monster|\Std $monster, Character $character, int $monsterHealth, int $characterHealth, bool $isCharacterVoided): array {

        if ($monster->gameMap->name === 'Purgatory') {
            return $this->monsterAmbushesPlayer($monster, $character, $monsterHealth, $characterHealth, $isCharacterVoided);
        }

        return $this->playerAmbushesMonster($monster, $character, $monsterHealth, $characterHealth, $isCharacterVoided);
    }

    public function monsterAmbushesPlayer(Monster|\Std $monster, Character $character, int $monsterHealth, int $characterHealth, bool $isCharacterVoided): array {
        if ($this->canEnemyAmbushPlayer($monster, $character)) {
            $this->battleLogs = $this->addMessage('The enemy sneaks through the shadows plotting and planning!', 'enemy-action-fired', $this->battleLogs);

            $characterHealth = $this->ambushPlayer($monster, $characterHealth);

        } else if ($characterHealth > 0 && $this->canPlayerAmbushEnemy($character, $monster)) {

            $this->battleLogs = $this->addMessage('Plotting and scheming, you manage to get the jump on the enemy!', 'action-fired', $this->battleLogs);

            $monsterHealth = $this->ambushMonster($character, $monsterHealth, $isCharacterVoided);
        }

        return [
            'monster_health'   => $monsterHealth,
            'character_health' => $characterHealth,
        ];
    }

    public function playerAmbushesMonster(Monster|\Std $monster, Character $character, int $monsterHealth, int $characterHealth, bool $isCharacterVoided): array {
        if ($this->canPlayerAmbushEnemy($character, $monster)) {
            $this->battleLogs = $this->addMessage('Plotting and scheming, you manage to get the jump on the enemy!', 'action-fired', $this->battleLogs);

            $monsterHealth = $this->ambushMonster($character, $monsterHealth, $isCharacterVoided);

        } else if ($monsterHealth > 0 && $this->canEnemyAmbushPlayer($monster, $character)) {

            $this->battleLogs = $this->addMessage('The enemy sneaks through the shadows plotting and planning!', 'enemy-action-fired', $this->battleLogs);

            $characterHealth = $this->ambushPlayer($monster, $characterHealth,);
        }

        return [
            'monster_health'   => $monsterHealth,
            'character_health' => $characterHealth,
        ];
    }

    protected function canPlayerAmbushEnemy(Character $character, Monster|\Std $monster): bool {
        $chance = $character->ambush_chance - $monster->ambush_resistance;
        $roll   = rand(1, 100);

        $roll = $roll + $roll * $chance;

        return $roll > 99;
    }

    protected function canEnemyAmbushPlayer(Monster|\Std $monster, Character $character): bool {
        $chance = $monster->ambush_chance - $character->ambush_resistance;
        $roll   = rand(1, 100);

        $roll = $roll + $roll * $chance;

        return $roll > 99;
    }

    public function ambushPlayer(Monster|\Std $monster, int $characterHealth): int {

        $totalDamage = ($monster->{$monster->damage_stat} * 1000);

        $characterHealth = $characterHealth - $totalDamage;

        $this->battleLogs = $this->addMessage('Look out child! It\'s an ambush! Enemy ambushes you for: ' . number_format($totalDamage), 'enemy-action-fired', $this->battleLogs);

        return $characterHealth;
    }

    public function ambushMonster(Character $character, int $monsterHealth, bool $voided): int {

        $damage = $this->characterInformationBuilder->setCharacter($character)->statMod($character->damage_stat);

        if ($voided) {
            $damage = $character->{$character->damage_stat};
        }

        $totalDamage = $damage * 2;

        $monsterHealth = $monsterHealth - $totalDamage;

        $this->battleLogs = $this->addMessage('You catch the enemy by surprise dealing: ' . number_format($totalDamage), 'action-fired', $this->battleLogs);

        return $monsterHealth;
    }
}
