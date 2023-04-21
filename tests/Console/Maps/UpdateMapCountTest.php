<?php

namespace Tests\Console\Maps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Events\UpdateGlobalCharacterCountBroadcast;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class UpdateMapCountTest extends TestCase
{

    use RefreshDatabase, CreateGameMap;

    public function testUpdateMapCount() {

        $this->createGameMap();

        Event::fake();

        $this->assertEquals(0, $this->artisan('update:map-count'));

        Event::assertDispatched(UpdateGlobalCharacterCountBroadcast::class);
    }

}
