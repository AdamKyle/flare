<?php

namespace Tests\Unit\Game\Battle\Console\Commands;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterInCelestialFight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Battle\Values\CelestialConjureType;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class ClearCelestialsTest extends TestCase
{
    use RefreshDatabase, CreateMonster, CreateNpc, CreateCelestials;

    private $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter([
            'gold'      => 999999999,
            'gold_dust' => 999999999,
        ]);
    }

    public function testClearCelestials()
    {

        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $character = $this->character->getCharacter();

        $celestialFight = $this->createCelestialFight([
            'monster_id'        => $monster->id,
            'character_id'      => null,
            'conjured_at'       => now(),
            'x_position'        => 16,
            'y_position'        => 16,
            'damaged_kingdom'   => false,
            'stole_treasury'    => false,
            'weakened_morale'   => false,
            'current_health'    => 1000,
            'max_health'        => 1000,
            'type'              => CelestialConjureType::PUBLIC,
        ]);

        DB::table('celestial_fights')->update(['updated_at' => now()->subDays(10)]);

        $this->createCharacterInCelestialFight([
            'character_id'             => $character->id,
            'celestial_fight_id'       => $celestialFight->id,
            'character_max_health'     => $character->getInformation()->buildHealth(),
            'character_current_health' => 10,
        ]);

        $this->assertEquals(0, $this->artisan('clear:celestials'));

        $this->assertTrue(CelestialFight::all()->isEmpty());
        $this->assertTrue(CharacterInCelestialFight::all()->isEmpty());
    }
}
