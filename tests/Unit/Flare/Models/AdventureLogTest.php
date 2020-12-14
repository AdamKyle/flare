<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\AdventureLog;
use Database\Seeders\GameSkillsSeeder;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Setup\Character\CharacterFactory;

class AdventureLogTest extends TestCase
{
    use RefreshDatabase, CreateAdventure;


    public function setUp(): void {
        parent::setUp();

        $adventure = $this->createNewAdventure();

        (new CharacterFactory)->createBaseCharacter()
                            ->updateCharacter(['can_move' => false])
                            ->levelCharacterUp(10)
                            ->createAdventureLog($adventure, [
                                'complete'             => true,
                                'in_progress'          => false,
                                'last_completed_level' => 1,
                            ])
                            ->getCharacter();
    }

    public function testCanGetCharacterForAdventure() {
        $this->assertNotNull(AdventureLog::first()->character);
    }
}
