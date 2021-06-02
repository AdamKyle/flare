<?php

namespace App\Game\Adventures\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Services\FightService;

class AdventureFightService {

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
    public function __construct(Character $character, Adventure $adventure) {

        $this->characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        $this->character     = $character;
        $this->adventure     = $adventure;

        $this->currentCharacterHealth = $this->characterInformation->buildHealth();
    }

    /**
     * Process the battle
     *
     * @return void
     */
    public function processBattle(): FightService {
        $this->monster              = $this->adventure->monsters()->inRandomOrder()->first();
        $healthRange                = explode('-', $this->monster->health_range);

        $this->currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + 10;

        $fightService = resolve(FightService::class, [
            'character' => $this->character,
            'monster'   => $this->monster,
        ]);

        $fightService->attack($this->character, $this->monster);

        return $fightService;
    }
}
