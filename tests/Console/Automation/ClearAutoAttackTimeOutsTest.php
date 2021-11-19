<?php

namespace Tests\Console\Automation;

use App\Flare\Models\AdventureLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;

class ClearAutoAttackTimeOutsTest extends TestCase
{

    use RefreshDatabase, CreateAdventure;

    public function testClearAutoAttackLocks() {

        $character = (new CharacterFactory())->createBaseCharacter()->updateCharacter([
            'is_attack_automation_locked' => true
        ])->getCharacter(false);

        $this->assertEquals(0, $this->artisan('clear:locked-auto-attack'));

        $this->assertFalse($character->refresh()->is_attack_automation_locked);
    }

}
