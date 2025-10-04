<?php

namespace Tests\Console\Character\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class AssignNewFactionsToCharactersTest extends TestCase
{
    use CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_assign_new_faction()
    {
        $character = $this->character->getCharacter();

        Artisan::call('assign:new-factions-to-characters');

        $character = $character->refresh();

        $this->assertNotNull($character->factions->isNotEmpty());
    }

    public function test_do_not_assign_new_faction()
    {
        $character = $this->character->getCharacter();

        $character->factions()->create([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'current_level' => 0,
            'current_points' => 1000,
            'points_needed' => 100000,
            'maxed' => false,
            'title' => 'N/A',
        ]);

        Artisan::call('assign:new-factions-to-characters');

        $character = $character->refresh();

        $this->assertCount(1, $character->factions);
    }
}
