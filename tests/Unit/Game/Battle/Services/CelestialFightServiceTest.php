<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Game\Battle\Services\CelestialFightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Battle\Values\CelestialConjureType;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class CelestialFightServiceTest extends TestCase
{
    use RefreshDatabase;

    use RefreshDatabase, CreateMonster, CreateNpc, CreateCelestials, CreateItem, CreateItemAffix;

    private $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->updateCharacter([
            'gold'      => 999999999,
            'gold_dust' => 999999999,
        ]);
    }


    public function testJoinFightAndUpdateHealthAfterFiveMinutes() {
        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $character = $this->character->getCharacter(true);

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

        $characterInCelestialFight = $this->createCharacterInCelestialFight([
            'character_id'             => $character->id,
            'celestial_fight_id'       => $celestialFight->id,
            'character_max_health'     => $character->getInformation()->buildHealth(),
            'character_current_health' => 10,
        ]);

        DB::table('character_in_celestial_fights')->update([
            'updated_at' => now()->subMinutes(10)
        ]);

        $celestialFightService = resolve(CelestialFightService::class);

        $celestialFightService->joinFight($character, $celestialFight);

        $characterInCelestialFight = $characterInCelestialFight->refresh();

        $this->assertEquals($character->getInformation()->buildHealth(), $characterInCelestialFight->character_current_health);
    }

    public function testJoinFightAndUpdateHealthWhenTheHealthDoesntMatch() {
        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $character = $this->character->getCharacter(true);

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

        $characterInCelestialFight = $this->createCharacterInCelestialFight([
            'character_id'             => $character->id,
            'celestial_fight_id'       => $celestialFight->id,
            'character_max_health'     => $character->getInformation()->buildHealth(),
            'character_current_health' => 25,
        ]);

        $celestialFightService = resolve(CelestialFightService::class);

        $celestialFightService->joinFight($character, $celestialFight);

        $characterInCelestialFight = $characterInCelestialFight->refresh();

        $this->assertEquals($character->getInformation()->buildHealth(), $characterInCelestialFight->character_current_health);
    }

    public function testFightCelestialCharacterDies() {
        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
            'str'                 => 1000,
            'dex'                 => 10000,
            'damage_stat'         => 'str',
            'attack_range'        => '10000-100000'
        ]);

        $this->createItemAffix();

        $character = $this->character->getCharacter(true);

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

        $characterInCelestialFight = $this->createCharacterInCelestialFight([
            'character_id'             => $character->id,
            'celestial_fight_id'       => $celestialFight->id,
            'character_max_health'     => $character->getInformation()->buildHealth(),
            'character_current_health' => 25,
        ]);

        $celestialFightService = resolve(CelestialFightService::class);

        $response = $celestialFightService->fight($character, $celestialFight, $characterInCelestialFight, 'attack');


        $this->assertEquals(200, $response['status']);
        $this->assertNotEmpty($response['fight']);
        $this->assertNotEmpty($response['logs']);
    }

    public function testFightCelestialMonsterDies() {
        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
            'str'                 => 0,
            'dex'                 => 0,
            'damage_stat'         => 'str',
            'attack_range'        => '1-2',
            'drop_check'          => 0.0,
        ]);

        $character = $this->character->levelCharacterUp(10)->inventoryManagement()->giveItem($this->createItem([
            'base_damage' => 10000,
            'type'        => 'weapon',
            'name'        => 'Weapon',
        ]))->equipLeftHand('Weapon')->getCharacterFactory()->getCharacter();

        $celestialFight = $this->createCelestialFight([
            'monster_id'        => $monster->id,
            'character_id'      => null,
            'conjured_at'       => now(),
            'x_position'        => 16,
            'y_position'        => 16,
            'damaged_kingdom'   => false,
            'stole_treasury'    => false,
            'weakened_morale'   => false,
            'current_health'    => 1,
            'max_health'        => 1,
            'type'              => CelestialConjureType::PUBLIC,
        ]);

        $characterInCelestialFight = $this->createCharacterInCelestialFight([
            'character_id'             => $character->id,
            'celestial_fight_id'       => $celestialFight->id,
            'character_max_health'     => $character->getInformation()->buildHealth(),
            'character_current_health' => 25,
        ]);

        $celestialFightService = resolve(CelestialFightService::class);


        $response = $celestialFightService->fight($character, $celestialFight, $characterInCelestialFight, 'attack');

        $this->assertEquals(200, $response['status']);
        $this->assertNotEmpty($response['logs']);
    }

    public function testFightCelestialNeitherDies() {
        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
            'str'                 => 0,
            'dex'                 => 0,
            'damage_stat'         => 'str',
            'attack_range'        => '1-2',
            'drop_check'          => 0.0,
        ]);

        $character = $this->character->levelCharacterUp(10)->getCharacter();

        $celestialFight = $this->createCelestialFight([
            'monster_id'        => $monster->id,
            'character_id'      => null,
            'conjured_at'       => now(),
            'x_position'        => 16,
            'y_position'        => 16,
            'damaged_kingdom'   => false,
            'stole_treasury'    => false,
            'weakened_morale'   => false,
            'current_health'    => 1,
            'max_health'        => 1,
            'type'              => CelestialConjureType::PUBLIC,
        ]);

        $characterInCelestialFight = $this->createCharacterInCelestialFight([
            'character_id'             => $character->id,
            'celestial_fight_id'       => $celestialFight->id,
            'character_max_health'     => $character->getInformation()->buildHealth(),
            'character_current_health' => 25,
        ]);

        $celestialFightService = resolve(CelestialFightService::class);


        $response = $celestialFightService->fight($character, $celestialFight, $characterInCelestialFight, 'attack');

        $this->assertEquals(200, $response['status']);
        $this->assertNotEmpty($response['fight']);
        $this->assertNotEmpty($response['logs']);
    }
}
