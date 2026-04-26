<?php

namespace Tests\Unit\Game\Exploration\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Exploration\Events\AutomationLogUpdate;
use App\Game\Exploration\Events\AutomationStatus;
use App\Game\Exploration\Events\AutomationTimeOut;
use App\Game\Exploration\Jobs\DelveExploration as DelveExplorationProcessing;
use App\Game\Exploration\Services\DelveExplorationAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;

class DelveAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DelveExplorationAutomationService $service;

    private Character $character;

    private Monster $monster;

    private Location $location;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(DelveExplorationAutomationService::class);

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
                'only_for_location_type' => LocationType::CAVE_OF_MEMORIES,
                'is_celestial_entity' => false,
                'is_raid_monster' => false,
                'is_raid_boss' => false,
                'raid_special_attack_type' => null,
            ])
            ->getMonster();

        $this->location = Location::factory()->create([
            'game_map_id' => $this->character->map->game_map_id,
            'type' => LocationType::CAVE_OF_MEMORIES,
            'minutes_between_delve_fights' => 7,
        ]);
    }

    public function testBeginAutomationCreatesDelveAutomation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        $automation = CharacterAutomation::first();

        $this->assertEquals(AutomationType::DELVE, $automation->type);
    }

    public function testBeginAutomationUsesMatchingCaveMonster(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        $automation = CharacterAutomation::first();

        $this->assertEquals($this->monster->id, $automation->monster_id);
    }

    public function testBeginAutomationPersistsAttackType(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->location, [
            ...$this->params(),
            'attack_type' => AttackTypeValue::CAST,
        ]);

        $automation = CharacterAutomation::first();

        $this->assertEquals(AttackTypeValue::CAST, $automation->attack_type);
    }

    public function testBeginAutomationSetsCompletedAtEightHoursFromNow(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        $automation = CharacterAutomation::first();

        $this->assertEquals($now->copy()->addHours(8)->toDateTimeString(), $automation->completed_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function testBeginAutomationCreatesDelveExploration(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        $delveExploration = DelveExploration::first();

        $this->assertEquals($this->character->id, $delveExploration->character_id);
        $this->assertEquals($this->monster->id, $delveExploration->monster_id);
        $this->assertEquals(AttackTypeValue::ATTACK, $delveExploration->attack_type);
    }

    public function testBeginAutomationDispatchesUpdateCharacterStatus(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testBeginAutomationDispatchesAutomationLogUpdate(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testBeginAutomationDispatchesAutomationTimeOut(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testBeginAutomationDispatchesDelveExplorationJob(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        Queue::assertPushed(DelveExplorationProcessing::class);
    }

    public function testStopExplorationDeletesDelveAutomation(): void
    {
        Event::fake();

        $this->createAutomation();
        $this->createDelveExploration();

        $this->service->stopExploration($this->character);

        $this->assertNull(
            CharacterAutomation::where('character_id', $this->character->id)
                ->where('type', AutomationType::DELVE)
                ->first()
        );
    }

    public function testStopExplorationCompletesActiveDelveExploration(): void
    {
        Event::fake();

        $this->createAutomation();
        $this->createDelveExploration();

        $this->service->stopExploration($this->character);

        $delveExploration = DelveExploration::first();

        $this->assertNotNull($delveExploration->completed_at);
    }

    public function testStopExplorationReturns422WhenNoAutomationExists(): void
    {
        Event::fake();

        $response = $this->service->stopExploration($this->character);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testStopExplorationClearsCharacterSurvivalCache(): void
    {
        Event::fake();

        $this->createAutomation();
        $this->createDelveExploration();

        Cache::put('can-character-survive-' . $this->character->id, true);

        $this->service->stopExploration($this->character);

        $this->assertFalse(Cache::has('can-character-survive-' . $this->character->id));
    }

    public function testStopExplorationDispatchesAutomationTimeOut(): void
    {
        Event::fake();

        $this->createAutomation();
        $this->createDelveExploration();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testStopExplorationDispatchesAutomationStatus(): void
    {
        Event::fake();

        $this->createAutomation();
        $this->createDelveExploration();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationStatus::class);
    }

    public function testStopExplorationDispatchesUpdateCharacterStatus(): void
    {
        Event::fake();

        $this->createAutomation();
        $this->createDelveExploration();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testStopExplorationDispatchesAutomationLogUpdate(): void
    {
        Event::fake();

        $this->createAutomation();
        $this->createDelveExploration();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testBeginAutomationDelaysDelveJobByLocationMinutesBetweenDelveFights(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->service->beginAutomation($this->character, $this->location, $this->params());

        Queue::assertPushed(DelveExplorationProcessing::class, function ($job) use ($now) {
            return $job->delay->toDateTimeString() === $now->copy()->addMinutes(7)->toDateTimeString();
        });

        Carbon::setTestNow();
    }

    private function params(): array
    {
        return [
            'attack_type' => AttackTypeValue::ATTACK,
            'pack_size' => 5,
        ];
    }

    private function createAutomation(): CharacterAutomation
    {
        return CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
    }

    private function createDelveExploration(): DelveExploration
    {
        return DelveExploration::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
    }
}