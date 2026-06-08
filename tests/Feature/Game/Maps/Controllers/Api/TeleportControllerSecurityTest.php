<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use Carbon\Carbon;
use App\Flare\Values\MapNameValue;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class TeleportControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testTeleportIgnoresClientCostAndTimeout(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/map/teleport/'.$character->id, [
                'x' => 80,
                'y' => 16,
                'cost' => 0,
                'timeout' => 0,
            ]);

        $response->assertOk();
        $character = $character->refresh();
        $this->assertSame(4000, $character->gold);
        $this->assertFalse($character->can_move);
        $this->assertSame('2026-01-01 12:01:00', $character->can_move_again_at->toDateTimeString());
    }
}
