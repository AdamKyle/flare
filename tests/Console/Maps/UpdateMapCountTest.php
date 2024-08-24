<?php

namespace Tests\Console\Maps;

use App\Game\Maps\Events\UpdateGlobalCharacterCountBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class UpdateMapCountTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function testUpdateMapCount()
    {

        $this->createGameMap();

        Event::fake();

        $this->assertEquals(0, $this->artisan('update:map-count'));

        Event::assertDispatched(UpdateGlobalCharacterCountBroadcast::class);
    }
}
