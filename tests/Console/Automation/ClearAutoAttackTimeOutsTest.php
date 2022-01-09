<?php

namespace Tests\Console\Automation;

use App\Flare\Models\AdventureLog;
use App\Flare\Models\Character;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;

class ClearAutoAttackTimeOutsTest extends TestCase
{

    use RefreshDatabase, CreateAdventure;

    public function testClearAutomationLockOut() {

        (new CharacterFactory())->createBaseCharacter()->updateCharacter([
            'is_attack_automation_locked' => true,
        ]);

        $this->assertEquals(0, $this->artisan('clear:locked-auto-attack'));

        $this->assertTrue(Character::where('is_attack_automation_locked', true)->get()->isEmpty());
    }

}
