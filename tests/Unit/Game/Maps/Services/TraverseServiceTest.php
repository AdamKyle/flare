<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Services\TraverseService;
use App\Game\Maps\Values\MapTileValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class TraverseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    public function testCanTravelRejectsMissingMap(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse(resolve(TraverseService::class)->canTravel(999999, $character));
    }

    public function testCanTravelAllowsSurface(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $surface = GameMap::factory()->create(['name' => MapNameValue::SURFACE]);

        $this->assertTrue(resolve(TraverseService::class)->canTravel($surface->id, $character));
    }

    public function testCanTravelRejectsOrdinaryNonSurfaceMap(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::TWISTED_MEMORIES]);

        $this->assertFalse(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelRejectsEventOnlyMapWhenEventMissing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::ICE_PLANE, 'only_during_event_type' => 8]);

        $this->assertFalse(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelAllowsEventOnlyMapWithMatchingEvent(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::ICE_PLANE, 'only_during_event_type' => 8]);
        Event::factory()->create(['type' => 8]);

        $this->assertTrue(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelRejectsLabyrinthWithoutRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::LABYRINTH]);

        $this->assertFalse(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelAllowsLabyrinthWithRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::LABYRINTH]);
        $item = Item::factory()->create(['effect' => ItemEffectsValue::LABYRINTH]);
        $character->inventory->slots()->create(['item_id' => $item->id]);

        $this->assertTrue(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelRejectsDungeonWithoutRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::DUNGEONS]);

        $this->assertFalse(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelAllowsDungeonWithRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::DUNGEONS]);
        $item = Item::factory()->create(['effect' => ItemEffectsValue::DUNGEON]);
        $character->inventory->slots()->create(['item_id' => $item->id]);

        $this->assertTrue(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelRejectsShadowPlaneWithoutRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::SHADOW_PLANE]);

        $this->assertFalse(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelAllowsShadowPlaneWithRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::SHADOW_PLANE]);
        $item = Item::factory()->create(['effect' => ItemEffectsValue::SHADOW_PLANE]);
        $character->inventory->slots()->create(['item_id' => $item->id]);

        $this->assertTrue(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelRejectsHellWithoutRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::HELL]);

        $this->assertFalse(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelAllowsHellWithRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::HELL]);
        $item = Item::factory()->create(['effect' => ItemEffectsValue::HELL]);
        $character->inventory->slots()->create(['item_id' => $item->id]);

        $this->assertTrue(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelRejectsPurgatoryWithoutRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::PURGATORY]);

        $this->assertFalse(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testCanTravelAllowsPurgatoryWithRequiredItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $map = GameMap::factory()->create(['name' => MapNameValue::PURGATORY]);
        $item = Item::factory()->create(['effect' => ItemEffectsValue::PURGATORY]);
        $character->inventory->slots()->create(['item_id' => $item->id]);

        $this->assertTrue(resolve(TraverseService::class)->canTravel($map->id, $character));
    }

    public function testTravelPersistsDestinationMapAndWalkableCoordinates(): void
    {
        $this->instance(MapTileValue::class, Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
            $mock->shouldReceive('setUp')->once()->andReturnSelf();
            $mock->shouldReceive('canWalk')->once()->with(32, 48)->andReturnTrue();
        }));
        Cache::put('coordinates', ['x' => [32], 'y' => [48]]);
        Queue::fake();
        EventFacade::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::LABYRINTH]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::LABYRINTH => []]);

        resolve(TraverseService::class)->travel($destination->id, $character);

        $map = $character->refresh()->map;
        $this->assertSame($destination->id, $map->game_map_id);
        $this->assertSame(32, $map->character_position_x);
        $this->assertSame(48, $map->character_position_y);
        $this->assertFalse($character->refresh()->can_move);
    }

    public function testTravelSetsExactlyTenSecondCooldown(): void
    {
        Carbon::setTestNow('2026-01-01 12:00:00');
        $this->instance(MapTileValue::class, Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
            $mock->shouldReceive('setUp')->andReturnSelf();
            $mock->shouldReceive('canWalk')->andReturnTrue();
        }));
        Cache::put('coordinates', ['x' => [32], 'y' => [48]]);
        Queue::fake();
        EventFacade::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::LABYRINTH]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::LABYRINTH => []]);

        resolve(TraverseService::class)->travel($destination->id, $character);

        $this->assertSame('2026-01-01 12:00:10', $character->refresh()->can_move_again_at->toDateTimeString());
        EventFacade::assertDispatched(MoveTimeOutEvent::class, fn (MoveTimeOutEvent $event): bool => $event->timeOut === 10 && ! $event->setSail && $event->traverse);
    }

    public function testTravelEmitsExistingMapAndStatusEvents(): void
    {
        $this->instance(MapTileValue::class, Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
            $mock->shouldReceive('setUp')->andReturnSelf();
            $mock->shouldReceive('canWalk')->andReturnTrue();
        }));
        Cache::put('coordinates', ['x' => [32], 'y' => [48]]);
        Queue::fake();
        EventFacade::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::LABYRINTH]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::LABYRINTH => []]);

        resolve(TraverseService::class)->travel($destination->id, $character);

        EventFacade::assertDispatched(UpdateMap::class);
        EventFacade::assertDispatched(MoveTimeOutEvent::class, fn (MoveTimeOutEvent $event): bool => $event->timeOut === 10 && ! $event->setSail && $event->traverse);
        EventFacade::assertDispatched(UpdateCharacterStatus::class);
        EventFacade::assertDispatched(UpdateBaseCharacterInformation::class);
        EventFacade::assertDispatched(UpdateTopBarEvent::class, fn (UpdateTopBarEvent $event): bool => ! $event->updateCharacterTops);
        EventFacade::assertDispatched(UpdateMonsterList::class);
    }

    public function testUpdateActionsPreservesRegularAndEventSpecificMonsterLists(): void
    {
        Queue::fake();
        EventFacade::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $regularMap = $character->map->gameMap;
        $eventMap = GameMap::factory()->create([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => 8,
        ]);
        $regularMonsters = [['id' => 1, 'name' => 'Regular Monster']];
        $eventMonsters = [
            'regular' => [['id' => 2, 'name' => 'Event Monster']],
            'easier' => [['id' => 3, 'name' => 'Easier Event Monster']],
        ];
        Cache::put('monsters', [
            $regularMap->name => $regularMonsters,
            $eventMap->name => $eventMonsters,
        ]);
        $traverseService = resolve(TraverseService::class);

        $traverseService->updateActions($regularMap->id, $character, $regularMap);

        $character->map()->update(['game_map_id' => $eventMap->id]);
        $character = $character->refresh();

        $traverseService->updateActions($eventMap->id, $character, $regularMap);

        $monsterLists = EventFacade::dispatched(UpdateMonsterList::class)
            ->map(fn (array $arguments): array => $arguments[0]->monsters)
            ->all();

        $this->assertSame([$regularMonsters, $eventMonsters['easier']], $monsterLists);
    }
}
