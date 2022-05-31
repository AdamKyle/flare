<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\BaseCharacterAttack;

class PvpAttack extends PvpBase {

    private $setUpFight;

    private array $battleMessages = [
        'attacker' => [],
        'defender' => [],
    ];

    private $healthObject = [
        'attacker_health' => 0,
        'defender_health' => 0,
    ];

    private BaseCharacterAttack $characterAttack;

    public function __construct(CharacterCacheData $characterCacheData, SetUpFight $setUpFight, BaseCharacterAttack $characterAttack) {
        parent::__construct($characterCacheData);

        $this->setUpFight      = $setUpFight;
        $this->characterAttack = $characterAttack;
    }

    public function getMessages() {
        return $this->battleMessages;
    }

    public function getHealthObject() {
        return $this->healthObject;
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

    public function attackPlayer(Character $attacker, Character $defender, array $healthObject, string $attackType) {
        $response = $this->characterAttack->doPvpAttack($attacker, $defender, $healthObject, $this->setUpFight->isAttackerVoided(), $attackType);

        $this->mergeMessages($response->getAttackerMessages(), 'attacker');
        $this->mergeMessages($response->getDefenderMessages(), 'defender');

        $defenderHealth = $response->getMonsterHealth();
        $attackerHealth = $response->getCharacterHealth();

        if ($defenderHealth <= 0) {
            $defenderHealth = 0;

            $this->mergeMessages([[
                'message' => 'You have been slain and must revive',
                'type'    => 'enemy-action',
            ]], 'defender');

            return true;
        }

        if ($attackerHealth <= 0) {
            $attackerHealth = 0;

            $this->mergeMessages([[
                'message' => 'You have been slain and must revive',
                'type'    => 'enemy-action',
            ]], 'attacker');
        }

        $this->healthObject = [
            'attacker_health' => $attackerHealth,
            'defender_health' => $defenderHealth,
        ];

        return false;
    }

    public function getAttackerHealth(): int {
        return $this->healthObject['attacker_health'];
    }

    public function getDefenderHealth(): int {
        return $this->healthObject['defender_health'];
    }

    protected function mergeMessages(array $messages, string $key) {
        $this->battleMessages[$key] = [...$this->battleMessages[$key], ...$messages];
    }
}
