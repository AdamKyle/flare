<?php

namespace Tests\Console\Exploration;

use App\Flare\Models\AdventureLog;
use App\Flare\Models\Character;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;

class ClearExplorationTimeOutsTest extends TestCase
{

    use RefreshDatabase, CreateAdventure;

    public function testClearAutomationLockOut() {

        (new CharacterFactory())->createBaseCharacter()->updateCharacter([
            'is_attack_automation_locked' => true,
        ]);

        $this->assertEquals(0, $this->artisan('clear:locked-exploration'));

        $this->assertTrue(Character::where('is_attack_automation_locked', true)->get()->isEmpty());
    }

}
