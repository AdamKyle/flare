<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\DailyGoldDustJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class UpdateKingdomsTest extends TestCase
{
    use RefreshDatabase;

    public function testCharactersKingdomGetsUpdate()
    {
        $character = (new CharacterFactory())->createBaseCharacter()
                                             ->givePlayerLocation()
                                             ->kingdomManagement()
                                             ->assignKingdom([
                                                 'last_walked' => now()
                                             ])
                                             ->assignBuilding()
                                             ->assignUnits()
                                             ->getCharacterFactory()
                                             ->getCharacter(false);

        DailyGoldDustJob::dispatch($character);

        $kingdom = $character->refresh()->kingdoms->first();

        $this->assertEquals(.50, $kingdom->current_morale);
    }
}
