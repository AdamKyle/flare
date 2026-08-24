<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Jobs\UpdateKingdomJob;
use App\Game\Kingdoms\Service\KingdomUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateKingdom;

class UpdateKingdomJobTest extends TestCase
{
    use CreateGameMap, CreateKingdom, RefreshDatabase;

    public function test_dispatched_job_updates_the_matching_kingdom(): void
    {
        $gameMap = $this->createGameMap();
        $kingdom = $this->createKingdom(['game_map_id' => $gameMap->id]);

        $capturedKingdom = null;

        $kingdomUpdateService = Mockery::mock(KingdomUpdateService::class);
        $kingdomUpdateService->shouldReceive('setKingdom')
            ->once()
            ->with(Mockery::on(function (Kingdom $suppliedKingdom) use (&$capturedKingdom) {
                $capturedKingdom = $suppliedKingdom;

                return true;
            }))
            ->andReturnSelf();
        $kingdomUpdateService->shouldReceive('updateKingdom')->once();

        $this->app->instance(KingdomUpdateService::class, $kingdomUpdateService);

        UpdateKingdomJob::dispatch($kingdom->id);

        $this->assertTrue($capturedKingdom->is($kingdom));
    }
}
