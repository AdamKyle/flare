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
     * @var Monster $monster
     */
    private $monster;

    /**
     * @var CharacterInformationBuilder $characterInformation
     */
    private $characterInformation;

    /**
     * Constructor
     *
     * @param Character $character
     * @param Adventure $adventure
     * @return void
     */
    public function __construct(Character $character, Adventure $adventure, string $attackType) {

        $this->characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        $this->character     = $character;
        $this->adventure     = $adventure;
        $this->attackType    = $attackType;

        $this->currentCharacterHealth = 0; //$this->characterInformation->buildHealth();
        $this->battleLogs    = [];
    }

    /**
     * Process the battle
     *
     * @return void
     */
    public function processFloor(): FightService {
        $encounters                 = rand(1, $this->adventure->monsters->count());

        for ($i = 1; $i <= $encounters; $i++) {
            $monster = $this->adventure->monsters()->inRandomOrder()->first();
            $message = 'You encounter a: ' . $monster->name;

            $this->battleLogs = $this->addMessage($message, 'info-encounter');

            $fightService = resolve(FightService::class, [
                'character' => $this->character,
                'monster'   => $monster,
            ]);

            $fightService->attack($this->character, $this->monster);


        }
    }

}
