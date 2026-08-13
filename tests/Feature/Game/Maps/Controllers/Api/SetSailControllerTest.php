<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateLocation;

class SetSailControllerTest extends TestCase
{
    use CreateLocation, RefreshDatabase;

    public function test_set_sail_moves_character_deducts_gold_and_starts_movement_timeout(): void
    {
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapName::SURFACE->value => []]);
        Queue::fake();

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Current Port',
            'x' => 16,
            'y' => 16,
            'is_port' => true,
        ]);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Destination Port',
            'x' => 80,
            'y' => 16,
            'is_port' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/map/set-sail/'.$character->id, [
                'x' => 80,
                'y' => 16,
                'cost' => 1000,
                'timeout' => 1,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame(['character_position_data', 'has_traversed'], array_keys($data));
        $this->assertSame(80, $data['character_position_data']['x_position']);
        $this->assertSame(16, $data['character_position_data']['y_position']);
        $this->assertFalse($data['has_traversed']);

        $character = $character->refresh();
        $this->assertSame(4000, $character->gold);
        $this->assertSame(80, $character->map->character_position_x);
        $this->assertSame(16, $character->map->character_position_y);
        $this->assertFalse($character->can_move);
        $this->assertNotNull($character->can_move_again_at);
    }

    public function test_set_sail_returns_422_and_does_not_move_or_charge_gold_when_cost_is_tampered_with(): void
    {
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapName::SURFACE->value => []]);
        Queue::fake();

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Current Port',
            'x' => 16,
            'y' => 16,
            'is_port' => true,
        ]);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Destination Port',
            'x' => 80,
            'y' => 16,
            'is_port' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/map/set-sail/'.$character->id, [
                'x' => 80,
                'y' => 16,
                'cost' => 1,
                'timeout' => 1,
            ]);

        $response->assertStatus(422);

        $character = $character->refresh();
        $this->assertSame(5000, $character->gold);
        $this->assertSame(16, $character->map->character_position_x);
        $this->assertSame(16, $character->map->character_position_y);
    }

    public function test_set_sail_returns_422_when_destination_is_the_current_port(): void
    {
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapName::SURFACE->value => []]);
        Queue::fake();

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Current Port',
            'x' => 16,
            'y' => 16,
            'is_port' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/map/set-sail/'.$character->id, [
                'x' => 16,
                'y' => 16,
                'cost' => 0,
                'timeout' => 0,
            ]);

        $response->assertStatus(422);

        $character = $character->refresh();
        $this->assertSame(5000, $character->gold);
        $this->assertSame(16, $character->map->character_position_x);
        $this->assertSame(16, $character->map->character_position_y);
    }
}
