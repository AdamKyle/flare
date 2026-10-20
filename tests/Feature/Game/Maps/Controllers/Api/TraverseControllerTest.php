<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Maps\Values\MapTileValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class TraverseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    public function testTraverseRejectsWhenMovementDisabled(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['can_move' => false])->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::SURFACE]);
        $originalMapId = $character->map->game_map_id;

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/traverse/'.$character->id, ['map_id' => $destination->id]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['invalid input'], $response->getData(true));
        $this->assertSame($originalMapId, $character->refresh()->map->game_map_id);
    }

    public function testTraverseRequiresMapId(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/traverse/'.$character->id, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('map_id', $response->getData(true)['errors']);
    }

    public function testTraverseRejectsNonIntegerMapId(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/traverse/'.$character->id, ['map_id' => 'surface'], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('map_id', $response->getData(true)['errors']);
    }

    public function testTraverseRefusesDestinationWithoutRequiredAccess(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::HELL]);
        $originalMapId = $character->map->game_map_id;

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/traverse/'.$character->id, ['map_id' => $destination->id]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('You are missing a required item to travel to that plane.', $response->getData(true)['message']);
        $this->assertSame($originalMapId, $character->refresh()->map->game_map_id);
    }

    public function testTraverseSucceedsWithSameResponseFlags(): void
    {
        Carbon::setTestNow('2026-01-01 12:00:00');
        $this->instance(MapTileValue::class, Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
            $mock->shouldReceive('setUp')->andReturnSelf();
            $mock->shouldReceive('canWalk')->andReturnTrue();
        }));
        Cache::put('coordinates', ['x' => [32], 'y' => [48]]);
        Queue::fake();
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::LABYRINTH]);
        $item = Item::factory()->create(['effect' => ItemEffectsValue::LABYRINTH]);
        $character->inventory->slots()->create(['item_id' => $item->id]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::LABYRINTH => []]);

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/traverse/'.$character->id, ['map_id' => $destination->id]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'can_access_hell_forged_shop' => false,
            'can_access_purgatory_chains_shop' => false,
            'can_access_twisted_earth_shop' => false,
            'can_access_hell_forged' => false,
            'can_access_purgatory_chains' => false,
            'can_access_twisted_earth' => false,
        ], $response->getData(true));
        $character = $character->refresh();
        $this->assertSame($destination->id, $character->map->game_map_id);
        $this->assertSame('2026-01-01 12:00:10', $character->can_move_again_at->toDateTimeString());
    }

    public function testTraverseDoesNotChangePositionWhenMapDoesNotExist(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();
        $originalMapId = $character->map->game_map_id;

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/traverse/'.$character->id, ['map_id' => 999999]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'message' => 'You are missing a required item to travel to that plane.',
        ], $response->getData(true));
        $map = $character->refresh()->map;
        $this->assertSame($originalMapId, $map->game_map_id);
        $this->assertSame(16, $map->character_position_x);
        $this->assertSame(16, $map->character_position_y);
    }

    public function testTraversePreservesExactTenSecondCooldownInResponseFlow(): void
    {
        Carbon::setTestNow('2026-02-01 09:30:00');
        $this->instance(MapTileValue::class, Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
            $mock->shouldReceive('setUp')->andReturnSelf();
            $mock->shouldReceive('canWalk')->andReturnTrue();
        }));
        Cache::put('coordinates', ['x' => [32], 'y' => [48]]);
        Queue::fake();
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::LABYRINTH]);
        $item = Item::factory()->create(['effect' => ItemEffectsValue::LABYRINTH]);
        $character->inventory->slots()->create(['item_id' => $item->id]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::LABYRINTH => []]);

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/traverse/'.$character->id, ['map_id' => $destination->id]);

        $this->assertSame(200, $response->getStatusCode());
        $character = $character->refresh();
        $this->assertSame($destination->id, $character->map->game_map_id);
        $this->assertFalse($character->can_move);
        $this->assertSame('2026-02-01 09:30:10', $character->can_move_again_at->toDateTimeString());
    }
}
