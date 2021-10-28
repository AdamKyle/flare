<?php

namespace Tests\Console\Adventure;

use App\Flare\Models\AdventureLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;

class DeleteAdventureLogsTest extends TestCase
{

    use RefreshDatabase, CreateAdventure;

    public function testDeleteAllAdventureLogs() {

        (new CharacterFactory())->createBaseCharacter()->createAdventureLog($this->createNewAdventure(), [
            'created_at' => Carbon::today()->subDays(100),
        ]);

        $this->assertEquals(0, $this->artisan('clean:adventure-logs'));

        $this->assertTrue(AdventureLog::all()->isEmpty());
    }

}
