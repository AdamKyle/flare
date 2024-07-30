<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\BaseCharacterAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\CharacterAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\PlayerHealing;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class PvpAttack extends PvpBase {


    private array $battleMessages = [
        'attacker' => [],
        'defender' => [],
    ];

    private array $healthObject = [
        'attacker_health' => 0,
        'defender_health' => 0,
    ];

    public function __construct(CharacterCacheData $characterCacheData,
                                private readonly SetUpFight $setUpFight,
                                private readonly PvpHealing $pvpHealing,
                                private readonly BaseCharacterAttack $characterAttack) {
        parent::__construct($characterCacheData);
    }

    public function getMessages(): array {
        return $this->battleMessages;
    }

    public function setUpPvpFight(Character $attacker, Character $defender, array $healthObject): array {

        if ($this->cache()->pvpCacheExists($attacker, $defender)) {
            return $this->cache()->fetchPvpCacheObject($attacker, $defender);
        }

        $healthObject = $this->setUpFight->setUp($attacker, $defender, $healthObject);

        $this->mergeMessages($this->setUpFight->getAttackerMessages(), 'attacker');
        $this->mergeMessages($this->setUpFight->getDefenderMessages(), 'defender');

        return $healthObject;
    }

    public function attackPlayer(Character $attacker, Character $defender, array $healthObject, string $attackType): void
    {
        $attackerVoided = $this->setUpFight->isAttackerVoided();
        $defenderVoided = $this->setUpFight->isEnemyVoided();

        $response = $this->characterAttack->doPvpAttack($attacker, $defender, $healthObject, $attackerVoided, $defenderVoided, $attackType);

        $this->mergeMessages($response->getAttackerMessages(), 'attacker');
        $this->mergeMessages($response->getDefenderMessages(), 'defender');

        $attackerHealth = $response->getCharacterHealth();
        $defenderHealth = $response->getMonsterHealth();

        $pvpHealing = $this->pvpHealing($defender, $attacker, $attackerHealth, $defenderHealth, $defenderVoided);

        $defenderHealth = $pvpHealing->getAttackerHealth();
        $attackerHealth = $pvpHealing->getDefenderHealth();

        $this->mergeMessages($pvpHealing->getAttackerMessages(), 'defender');
        $this->mergeMessages($pvpHealing->getDefenderMessages(), 'attacker');

        $pvpLifeStealing = $this->pvpLifeStealing($defender, $attacker, $attackerHealth, $defenderHealth, $defenderVoided);

        $defenderHealth = $pvpLifeStealing->getAttackerHealth();
        $attackerHealth = $pvpLifeStealing->getDefenderHealth();

        $this->mergeMessages($pvpLifeStealing->getAttackerMessages(), 'defender');
        $this->mergeMessages($pvpLifeStealing->getDefenderMessages(), 'attacker');


        if ($defenderHealth <= 0) {
            $defenderHealth = 0;

            $this->mergeMessages([[
                'message' => 'You have been slain and must revive',
                'type'    => 'enemy-action',
            ]], 'defender');

            $this->mergeMessages([[
                'message' => 'You have slaughtered the player. They have been moved away.',
                'type'    => 'enemy-action',
            ]], 'attacker');
        }

        if ($attackerHealth <= 0) {
            $attackerHealth = 0;

            $this->mergeMessages([[
                'message' => 'You have been slain and must revive',
                'type'    => 'enemy-action',
            ]], 'defender');

            $this->mergeMessages([[
                'message' => 'You have slaughtered the player. They have been moved away.',
                'type'    => 'enemy-action',
            ]], 'attacker');
        }

        $this->healthObject = [
            'attacker_health' => $attackerHealth,
            'defender_health' => $defenderHealth,
        ];
    }

    public function getAttackerHealth(): int {
        return $this->healthObject['attacker_health'];
    }

    public function getDefenderHealth(): int {
        return $this->healthObject['defender_health'];
    }

    protected function mergeMessages(array $messages, string $key): void
    {
        $this->battleMessages[$key] = array_merge($this->battleMessages[$key], $messages);
    }

    private function pvpHealing(Character $defender, Character $attacker, int $attackerHealth, int $defenderHealth, bool $isVoided): PvpHealing {
        $this->pvpHealing->setAttacker($defender);
        $this->pvpHealing->setDefender($attacker);
        $this->pvpHealing->setDefenderHealth($defenderHealth);
        $this->pvpHealing->setAttackerHealth($attackerHealth);
        $this->pvpHealing->setDefenderIsVoided($isVoided);
        $this->pvpHealing->defenderHeal($defender);

        return $this->pvpHealing;
    }

    private function pvpLifeStealing(Character $defender, Character $attacker, int $attackerHealth, int $defenderHealth, bool $isVoided): PvpHealing {
        $this->pvpHealing->setAttacker($defender);
        $this->pvpHealing->setDefender($attacker);
        $this->pvpHealing->setDefenderHealth($attackerHealth);
        $this->pvpHealing->setAttackerHealth($defenderHealth);
        $this->pvpHealing->setDefenderIsVoided($isVoided);
        $this->pvpHealing->stealLife($defender);

        return $this->pvpHealing;
    }
}
