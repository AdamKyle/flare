<?php

namespace App\Game\Adventures\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Services\FightService;
use App\Game\Adventures\Traits\CreateBattleMessages;

class AdventureFightService {

    use CreateBattleMessages;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Adventure $adventure
     */
    private $adventure;


    /**
     * @var CharacterInformationBuilder $characterInformation
     */
    private $characterInformation;

    private $fightService;

    private $battleLogs = [];

    private $characterWon = true;

    private $attackType = null;

    private $currentCharacterHealth;

    /**
     * Constructor
     *
     * @param Character $character
     * @param Adventure $adventure
     * @return void
     */
    public function __construct(CharacterInformationBuilder $characterInformationBuilder, FightService $fightService) {

        $this->characterInformation = $characterInformationBuilder;
        $this->fightService         = $fightService;
    }

    public function setCharacter(Character $character, string $attackType): AdventureFightService {
        $this->character     = $character;
        $this->attackType    = $attackType;

        $voided = $this->isAttackVoided($attackType);

        $this->characterInformation   = $this->characterInformation->setCharacter($character);

        $this->currentCharacterHealth = $this->characterInformation->buildHealth($voided);

        return $this;
    }

    public function setAdventure(Adventure $adventure): AdventureFightService {
        $this->adventure = $adventure;

        return $this;
    }

    /**
     * Process the battle
     *
     * @return void
     */
    public function processFloor() {
        $encounters                 = rand(1, $this->adventure->monsters->count());

        for ($i = 1; $i <= $encounters; $i++) {
            if ($this->characterWon) {
                $monster = $this->adventure->monsters()->inRandomOrder()->first();
                $message = 'You encounter a: ' . $monster->name;

                $this->battleLogs = $this->addMessage($message, 'info-encounter');


                $this->characterWon = $this->fightService->processFight($this->character, $monster, $this->attackType);

                $this->battleLogs = [...$this->battleLogs, ...$this->fightService->getBattleMessages()];

                $this->fightService->reset();
            }
        }
    }

    public function getLogs(): array {
        return  [
            'reward_info' => [],
            'messages'    => $this->battleLogs,
        ];
    }

    protected function isAttackVoided(string $attackType): bool {
        return str_contains($attackType, 'voided');
    }

}
