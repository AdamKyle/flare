<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\NpcTypes;
use App\Game\Kingdoms\Service\KingdomUpdateService;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateNpc;
use Tests\Unit\Game\Kingdoms\Helpers\CreateKingdomHelper;

class KingdomUpdateServiceTest extends TestCase
{
    use CreateGameBuilding, CreateKingdomHelper, CreateNpc, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?KingdomUpdateService $kingdomUpdateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->kingdomUpdateService = resolve(KingdomUpdateService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->kingdomUpdateService = null;
    }

    public function test_can_update_kingdom()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $this->assertNotNull($this->kingdomUpdateService->getKingdom());
    }

    public function test_hands_kingdom_to_npc()
    {

        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $this->createNpc([
            'type' => NpcTypes::KINGDOM_HOLDER,
        ]);

        $kingdom->update([
            'last_walked' => now()->subDays(120),
        ]);

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $this->assertNull($this->kingdomUpdateService->getKingdom());

        $totalKingdoms = Kingdom::where('npc_owned', true)->count();

        $this->assertEquals(1, $totalKingdoms);
    }

    public function test_destroy_npc_kingdom()
    {
        $kingdom = $this->createKingdom([
            'character_id' => null,
            'npc_owned' => true,
            'game_map_id' => GameMap::first()->id,
            'updated_at' => now()->subDays(120),
        ]);

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $this->assertNull($this->kingdomUpdateService->getKingdom());

        $this->assertEquals(0, Kingdom::all()->count());
    }

    public function test_over_populated_and_takes_all_treasury()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_population' => $kingdom->max_population + 1,
            'treasury' => 10000,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        // Treasury Update ...
        $this->assertEquals(1, $kingdom->treasury);
    }

    public function test_over_populated_and_takes_some_treasury()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_population' => $kingdom->max_population + 1,
            'treasury' => 20000,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        // Treasury Update ...
        $this->assertEquals(10100, $kingdom->treasury);
    }

    public function test_over_populated_and_takes_all_gold_bars()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_population' => $kingdom->max_population + 1,
            'treasury' => 0,
            'gold_bars' => 1,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertEquals(0, $kingdom->gold_bars);
    }

    public function test_over_populated_and_takes_some_gold_bars()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_population' => $kingdom->max_population + 1,
            'treasury' => 0,
            'gold_bars' => 2,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertEquals(1, $kingdom->gold_bars);
    }

    public function test_over_populated_and_takes_all_character_gold()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_population' => $kingdom->max_population + 1,
            'treasury' => 0,
            'gold_bars' => 0,
        ]);

        $kingdom = $kingdom->refresh();

        $character = $kingdom->character;

        $character->update([
            'gold' => 10000,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertEquals(0, $kingdom->character->gold);
    }

    public function test_over_populated_and_takes_some_character_gold()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_population' => $kingdom->max_population + 1,
            'treasury' => 0,
            'gold_bars' => 0,
        ]);

        $kingdom = $kingdom->refresh();

        $character = $kingdom->character;

        $character->update([
            'gold' => 20000,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertEquals(10000, $kingdom->character->gold);
    }

    public function test_do_not_update_kingdom_treasury_when_maxed()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 0.50,
            'treasury' => KingdomMaxValue::MAX_TREASURY,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertEquals(KingdomMaxValue::MAX_TREASURY, $kingdom->treasury);
    }

    public function test_update_kingdom_treasury_when_treasury_is_zero()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 0.50,
            'treasury' => 0,
            'last_walked' => now(),
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertGreaterThan(0, $kingdom->treasury);
    }

    public function test_update_kingdom_treasury_when_treasury_is_not_zero()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 0.50,
            'treasury' => 10000,
            'last_walked' => now(),
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertGreaterThan(10000, $kingdom->treasury);
    }

    public function test_give_no_resources_to_kingdom_when_buildings_durability_is_zero()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 2.0,
            'current_wood' => 0,
            'max_wood' => 100,
            'current_clay' => 0,
            'max_clay' => 100,
            'current_iron' => 0,
            'max_iron' => 100,
            'current_stone' => 0,
            'max_stone' => 100,
        ]);

        $kingdom = $kingdom->refresh();

        foreach ($kingdom->buildings as $building) {
            $building->update([
                'current_durability' => 0,
            ]);
        }

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $types = ['wood', 'stone', 'iron', 'clay'];

        foreach ($types as $type) {
            $this->assertEquals(0, $kingdom->{'current_'.$type});
        }
    }

    public function test_give_some_resources_to_kingdom_when_buildings_damaged()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 2.0,
            'current_wood' => 0,
            'max_wood' => 100,
            'current_clay' => 0,
            'max_clay' => 100,
            'current_iron' => 0,
            'max_iron' => 100,
            'current_stone' => 0,
            'max_stone' => 100,
        ]);

        $kingdom = $kingdom->refresh();

        foreach ($kingdom->buildings as $building) {
            $building->update([
                'current_durability' => $building->max_durability - 10,
            ]);
        }

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $types = ['wood', 'stone', 'iron', 'clay'];

        foreach ($types as $type) {
            $this->assertGreaterThan(0, $kingdom->{'current_'.$type});
        }
    }

    public function test_give_full_resources_to_kingdom_when_buildings_are_not_damaged()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 2.0,
            'current_wood' => 0,
            'max_wood' => 100,
            'current_clay' => 0,
            'max_clay' => 100,
            'current_iron' => 0,
            'max_iron' => 100,
            'current_stone' => 0,
            'max_stone' => 100,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $types = ['wood', 'stone', 'iron', 'clay'];

        foreach ($types as $type) {
            $this->assertEquals(100, $kingdom->{'current_'.$type});
        }
    }

    public function test_do_not_update_population_when_farm_durability_is_at_zero()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 2.0,
            'current_population' => 0,
            'max_population' => 100,
        ]);

        $kingdom = $kingdom->refresh();

        foreach ($kingdom->buildings as $building) {
            $building->update([
                'current_durability' => 0,
            ]);
        }

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertEquals(0, $kingdom->current_population);
    }

    public function test_only_give_some_population_based_on_durability_of_farm()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 2.0,
            'current_population' => 0,
            'max_population' => 100,
        ]);

        $kingdom = $kingdom->refresh();

        foreach ($kingdom->buildings as $building) {
            $building->update([
                'current_durability' => $building->current_durability - 10,
            ]);
        }

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertGreaterThan(0, $kingdom->current_population);
    }

    public function test_give_full_population()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $kingdom->update([
            'current_morale' => 2.0,
            'current_population' => 0,
            'max_population' => 100,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNotNull($kingdom);

        $this->assertEquals(100, $kingdom->current_population);
    }

    public function test_update_character_server_message()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $this->bailIfMissingKeyElements($kingdom);

        $character = $kingdom->character;

        $character->user->update([
            'show_kingdom_update_messages' => true,
        ]);

        $kingdom = $kingdom->refresh();

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id' => '1',
            'user_id' => $character->refresh()->user->id,
            'ip_address' => '1',
            'user_agent' => '1',
            'payload' => '1',
            'last_activity' => 1602801731,
        ]]);

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        // We just let the event fall through more for code coverage then anything else.
        // The Event::fake states it was not dispatched, when it was.
        $this->assertNotNull($kingdom);
    }

    public function test_update_kingdom_to_not_be_protected()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $kingdom->update([
            'protected_until' => now()->subWeeks(4),
        ]);

        $character = $kingdom->character;

        $character->user->update([
            'show_kingdom_update_messages' => true,
        ]);

        $kingdom = $kingdom->refresh();

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id' => '1',
            'user_id' => $character->refresh()->user->id,
            'ip_address' => '1',
            'user_agent' => '1',
            'payload' => '1',
            'last_activity' => 1602801731,
        ]]);

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertNull($kingdom->protected_until);
    }

    public function test_kingdom_suffer_morale_damage_for_not_being_walked()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $kingdom->update([
            'last_walked' => now()->subDays(31),
        ]);

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $this->kingdomUpdateService->getKingdom();

        $this->assertLessThan(1.0, $kingdom->current_morale);
    }

    public function test_hand_kingdom_to_npc_when_last_walked_is_null()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $kingdom->update([
            'last_walked' => null,
        ]);

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->npc_owned);
    }

    public function test_handle_when_a_kingdom_gives_resources_but_doesnt_state_what_resource()
    {

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('building with no resource increase does not exist');

        $kingdom = $this->createKingdomForCharacter($this->character);

        $kingdom->buildings()->create([
            'game_building_id' => $this->createGameBuilding([
                'name' => 'building with no resource increase',
                'is_resource_building' => true,

            ])->id,
            'kingdom_id' => $kingdom->id,
            'level' => 1,
            'max_defence' => 100,
            'max_durability' => 100,
            'current_durability' => 100,
            'current_defence' => 100,
        ]);

        $kingdom = $kingdom->refresh();

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertNotNull($kingdom);
    }

    public function test_kingdom_has_too_much_population_and_cannot_afford_the_cost()
    {
        $kingdom = $this->createKingdomForCharacter($this->character);

        $kingdom->update([
            'current_population' => 2_000_000_000,
            'treasury' => 0,
        ]);

        $character = $kingdom->character;

        $this->kingdomUpdateService->setKingdom($kingdom)->updateKingdom();

        $kingdomLog = KingdomLog::where('character_id', $character->id)->first();

        $this->assertNotNull($kingdomLog);

        // Character should have lost their kingdom
        $this->assertEmpty($character->kingdoms);
    }

    protected function bailIfMissingKeyElements(?Kingdom $kingdom)
    {
        if (is_null($kingdom)) {
            $this->fail('Could not create kingdom. Character is not setup.');
        }

        if (is_null($this->kingdomUpdateService)) {
            $this->fail('Kingdom update service is not setup.');
        }
    }
}
