<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;

class PvpAttack extends PvpBase {

    private $setUpFight;

    private array $battleMessages = [
        'attacker' => [],
        'defender' => [],
    ];

    public function __construct(CharacterCacheData $characterCacheData, SetUpFight $setUpFight) {
        parent::__construct($characterCacheData);

        $this->setUpFight = $setUpFight;
    }

    public function getMessages() {
        return $this->battleMessages;
    }

    public function setUpPvpFight(Character $attacker, Character $defender) {
        $this->setUpFight->setUp($attacker, $defender);

        $this->mergeMessages($this->setUpFight->getAttackerMessages(), 'attacker');
        $this->mergeMessages($this->setUpFight->getDefenderMessages(), 'defender');

        dd($this->battleMessages);
    }

    protected function mergeMessages(array $messages, string $key) {
        $this->battleMessages[$key] = [...$this->battleMessages[$key], $messages];
    }
}
