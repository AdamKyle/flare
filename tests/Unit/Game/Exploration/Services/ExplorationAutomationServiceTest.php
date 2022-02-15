<?php

namespace Tests\Unit\Game\Exploration\Services;

use App\Game\Exploration\Services\ExplorationAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class ExplorationAutomationServiceTest extends TestCase
{
    use RefreshDatabase, CreateMonster, CreateCharacterAutomation, CreateItemAffix, CreateLocation, CreateGameSkill;

    private $character;

    private $explorationAutomation;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()
                                                   ->givePlayerLocation()
                                                   ->assignSkill($this->createGameSkill([
                                                       'fight_time_out_mod_bonus_per_level' => 0.001
                                                   ]), 999)
                                                   ->assignFactionSystem();


        $this->explorationAutomation = resolve(ExplorationAutomationService::class);

        $this->createItemAffix(); // when random items are generated.
    }
}
