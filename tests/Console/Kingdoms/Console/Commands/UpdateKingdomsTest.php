<?php

namespace Tests\Console\Kingdoms\Console\Commands;

use App\Game\Kingdoms\Jobs\UpdateKingdomJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateKingdom;

class UpdateKingdomsTest extends TestCase
{
    use CreateGameMap, CreateKingdom, RefreshDatabase;

    public function test_dispatches_update_kingdom_job_for_every_kingdom_on_the_kingdom_jobs_connection(): void
    {
        Queue::fake();

        $gameMap = $this->createGameMap();
        $firstKingdom = $this->createKingdom(['game_map_id' => $gameMap->id]);
        $secondKingdom = $this->createKingdom(['game_map_id' => $gameMap->id]);

        Artisan::call('update:kingdoms');

        Queue::assertPushed(UpdateKingdomJob::class, function (UpdateKingdomJob $job) use ($firstKingdom) {
            return $job->kingdomId === $firstKingdom->id && $job->connection === 'kingdom_jobs';
        });

        Queue::assertPushed(UpdateKingdomJob::class, function (UpdateKingdomJob $job) use ($secondKingdom) {
            return $job->kingdomId === $secondKingdom->id && $job->connection === 'kingdom_jobs';
        });
    }
}
