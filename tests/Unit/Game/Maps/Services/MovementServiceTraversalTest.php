<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class MovementServiceTraversalTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testGetMapsToTraverseOmitsCanTraverseFalseMap(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $inaccessibleMap = GameMap::factory()->create(['name' => MapNameValue::LABYRINTH, 'can_traverse' => false]);
        $accessibleMap = GameMap::factory()->create(['name' => MapNameValue::DUNGEONS, 'can_traverse' => true]);

        $maps = resolve(MovementService::class)->getMapsToTraverse($character);
        $mapIds = collect($maps)->pluck('id')->all();

        $this->assertNotContains($inaccessibleMap->id, $mapIds);
        $this->assertContains($accessibleMap->id, $mapIds);
    }

    public function testGetMapsToTraverseHidesRequiredLocationMapWhenNotAtLocation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();
        $requiredLocation = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
        ]);
        $destination = GameMap::factory()->create([
            'name' => MapNameValue::LABYRINTH,
            'can_traverse' => true,
            'required_location_id' => $requiredLocation->id,
        ]);

        $mapIds = collect(resolve(MovementService::class)->getMapsToTraverse($character))->pluck('id')->all();

        $this->assertNotContains($destination->id, $mapIds);
    }

    public function testGetMapsToTraverseShowsRequiredLocationMapWhenStandingAtRequiredLocation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();
        $requiredLocation = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 16,
            'y' => 16,
        ]);
        $destination = GameMap::factory()->create([
            'name' => MapNameValue::LABYRINTH,
            'can_traverse' => true,
            'required_location_id' => $requiredLocation->id,
        ]);

        $mapIds = collect(resolve(MovementService::class)->getMapsToTraverse($character))->pluck('id')->all();

        $this->assertContains($destination->id, $mapIds);
    }

    public function testGetMapsToTraverseHidesEventMapWithoutEvent(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create([
            'name' => MapNameValue::ICE_PLANE,
            'can_traverse' => true,
            'only_during_event_type' => 8,
        ]);

        $mapIds = collect(resolve(MovementService::class)->getMapsToTraverse($character))->pluck('id')->all();

        $this->assertNotContains($destination->id, $mapIds);
    }

    public function testGetMapsToTraverseShowsEventMapWhenEventExists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create([
            'name' => MapNameValue::ICE_PLANE,
            'can_traverse' => true,
            'only_during_event_type' => 8,
        ]);
        Event::factory()->create(['type' => 8]);

        $mapIds = collect(resolve(MovementService::class)->getMapsToTraverse($character))->pluck('id')->all();

        $this->assertContains($destination->id, $mapIds);
    }

    public function testUpdateCharacterPlaneReturnsErrorForInaccessibleDestination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::HELL]);
        $originalMapId = $character->map->game_map_id;

        $response = resolve(MovementService::class)->updateCharacterPlane($destination->id, $character);

        $this->assertSame([
            'message' => 'You are missing a required item to travel to that plane.',
            'status' => 422,
        ], $response);
        $this->assertSame($originalMapId, $character->refresh()->map->game_map_id);
    }

    public function testUpdateCharacterPlaneReturnsDestinationShopFlags(): void
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
        $item = Item::factory()->create(['effect' => ItemEffectsValue::LABYRINTH]);
        $character->inventory->slots()->create(['item_id' => $item->id]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::LABYRINTH => []]);

        $response = resolve(MovementService::class)->updateCharacterPlane($destination->id, $character);

        $this->assertSame([
            'status' => 200,
            'can_access_hell_forged_shop' => false,
            'can_access_purgatory_chains_shop' => false,
            'can_access_twisted_earth_shop' => false,
            'can_access_hell_forged' => false,
            'can_access_purgatory_chains' => false,
            'can_access_twisted_earth' => false,
        ], $response);
        $this->assertSame($destination->id, $character->refresh()->map->game_map_id);
    }

    public function testGiveLocationRewardDoesNotCreateSlotWithoutRewardItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'quest_reward_item_id' => null,
        ]);
        $slotCount = $character->inventory->slots()->count();

        resolve(MovementService::class)->giveLocationReward($character, $location);

        $this->assertSame($slotCount, $character->inventory->slots()->count());
    }

    public function testGiveLocationRewardCreatesQuestRewardItemOnce(): void
    {
        EventFacade::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $rewardItem = Item::factory()->create(['name' => 'Gate Key', 'type' => 'quest']);
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'quest_reward_item_id' => $rewardItem->id,
        ]);

        resolve(MovementService::class)->giveLocationReward($character, $location);

        $this->assertSame(1, $character->inventory->slots()->where('item_id', $rewardItem->id)->count());
        EventFacade::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === 'You found: '.$rewardItem->affix_name);
        EventFacade::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testGiveLocationRewardDoesNotDuplicateAlreadyOwnedQuestItem(): void
    {
        EventFacade::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $rewardItem = Item::factory()->create(['type' => 'quest']);
        $character->inventory->slots()->create(['item_id' => $rewardItem->id]);
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'quest_reward_item_id' => $rewardItem->id,
        ]);

        resolve(MovementService::class)->giveLocationReward($character, $location);

        $this->assertSame(1, $character->inventory->slots()->where('item_id', $rewardItem->id)->count());
        EventFacade::assertNotDispatched(ServerMessageEvent::class);
        EventFacade::assertNotDispatched(UpdateCharacterStatus::class);
    }
}
