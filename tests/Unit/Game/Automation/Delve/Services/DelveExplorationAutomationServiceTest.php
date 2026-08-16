<?php

namespace Tests\Unit\Game\Automation\Delve\Services;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Game\Automation\Delve\Jobs\DelveExploration as DelveExplorationProcessing;
use App\Game\Automation\Delve\Services\DelveExplorationAutomationService;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class DelveExplorationAutomationServiceTest extends TestCase
{
    use CreateCharacterAutomation, CreateDelveExploration, CreateLocation, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?DelveExplorationAutomationService $delveExplorationAutomationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->delveExplorationAutomationService = resolve(DelveExplorationAutomationService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->delveExplorationAutomationService = null;
    }

    public function test_begin_automation_creates_the_character_automation_and_delve_exploration_records(): void
    {
        Queue::fake();
        Event::fake();

        $character = $this->character->getCharacter();

        $location = $this->createLocation([
            'type' => LocationType::CAVE_OF_MEMORIES->value,
            'game_map_id' => $character->map->game_map_id,
            'minutes_between_delve_fights' => 3,
        ]);

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);

        $this->delveExplorationAutomationService->beginAutomation($character, $location, [
            'attack_type' => AttackType::ATTACK->value,
            'pack_size' => 1,
        ]);

        $this->assertSame(1, CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE->value)->count());
        $this->assertSame(1, DelveExploration::where('character_id', $character->id)->whereNull('completed_at')->count());
        Queue::assertPushed(DelveExplorationProcessing::class);
    }

    public function test_stop_exploration_returns_error_when_character_has_no_delve_automation(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->delveExplorationAutomationService->stopExploration($character);

        $this->assertSame('Nope. You don\'t own that.', $result['message']);
    }

    public function test_stop_exploration_removes_the_running_automation(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $this->delveExplorationAutomationService->stopExploration($character);

        $this->assertSame(0, CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE->value)->count());
        $this->assertNotNull(DelveExploration::where('character_id', $character->id)->first()->completed_at);
    }
}
