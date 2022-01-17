<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\DailyGoldDustJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class DailyGoldDustTest extends TestCase
{
    use RefreshDatabase;

    public function testCharactersGetsGoldDust()
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold_dust' => 0])->getCharacter(false);

        DailyGoldDustJob::dispatch($character);

        $character = $character->refresh();

        $this->assertGreaterThan(0, $character->gold_dust);
    }
}
