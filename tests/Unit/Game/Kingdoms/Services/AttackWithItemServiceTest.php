<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Models\Character;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Service\AttackWithItemsService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Unit\Game\Kingdoms\Helpers\CreateKingdomHelper;

class AttackWithItemServiceTest extends TestCase
{

    use CreateGameBuilding, CreateKingdomHelper, RefreshDatabase, CreateItem, CreateGameMap, CreateNpc, CreateFactionLoyalty;

    private ?CharacterFactory $character;

    private ?CharacterFactory $defendingKingdomCharacter;

    private ?AttackWithItemsService $attackWithItemService;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->defendingKingdomCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->attackWithItemService = resolve(AttackWithItemsService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->defendingKingdomCharacter = null;
        $this->attackWithItemService = null;
    }

    public function testItemsDoNotExistInCharacterInventory()
    {
        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $result = $this->attackWithItemService->useItemsOnKingdom($this->character->getCharacter(), $defendersKingdom, [999, 998, 997]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You don\'t own these items.', $result['message']);
    }

    public function testYouCannotAttackYourOwnKingdoms()
    {

        $slotIds = $this->character->inventoryManagement()->giveItemMultipleTimes($this->createItem([
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]), 3)->getSlotIds();

        $charactersOwnKingdom = $this->createKingdomForCharacter($this->character);

        $result = $this->attackWithItemService->useItemsOnKingdom($this->character->getCharacter(), $charactersOwnKingdom, $slotIds);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You cannot attack your own kingdoms.', $result['message']);
    }

    public function testCannotAttackProtectedKingdom()
    {

        $slotIds = $this->character->inventoryManagement()->giveItemMultipleTimes($this->createItem([
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]), 3)->getSlotIds();

        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $defendersKingdom->update([
            'protected_until' => now()->addDays(7)
        ]);

        $defendersKingdom = $defendersKingdom->refresh();

        $result = $this->attackWithItemService->useItemsOnKingdom($this->character->getCharacter(), $defendersKingdom, $slotIds);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('This kingdom is currently under The Creators protection and cannot be targeted right now.', $result['message']);
    }

    public function testCannotAttackKingdomNotOnTheSamePlane()
    {

        $slotIds = $this->character->inventoryManagement()->giveItemMultipleTimes($this->createItem([
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]), 3)->getSlotIds();

        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $differentMap = $this->createGameMap(['name' => 'far away place']);

        $defendersKingdom->update([
            'game_map_id' => $differentMap->id
        ]);

        $defendersKingdom = $defendersKingdom->refresh();

        $result = $this->attackWithItemService->useItemsOnKingdom($this->character->getCharacter(), $defendersKingdom, $slotIds);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You need to be on the same plane as the kingdom you want to attack with items.', $result['message']);
    }

    public function testDropItemOnKingdom()
    {

        Event::fake();

        $slotIds = $this->character->inventoryManagement()->giveItemMultipleTimes($this->createItem([
            'damages_kingdoms' => true,
            'kingdom_damage' => 0
        ]), 1)->getSlotIds();

        $this->defendingKingdomCharacter->assignFactionSystem();

        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $this->assignFactionLoyaltyToKingdom($defendersKingdom->character, .05);

        $character = $this->character->getCharacter();

        $result = $this->attackWithItemService->useItemsOnKingdom($character, $defendersKingdom->refresh(), $slotIds);

        $this->assertEquals(200, $result['status']);

        $this->assertEquals('Dropped items on kingdom!', $result['message']);

        $this->assertNotEmpty(KingdomLog::where('character_id', $character->id)->get());

        $defendersKingdom = $defendersKingdom->refresh();

        $this->assertGreaterThan(0, $defendersKingdom->current_morale);

        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testDropItemsOnKingdomAndMoraleBecomesZero()
    {

        Event::fake();

        $slotIds = $this->character->inventoryManagement()->giveItemMultipleTimes($this->createItem([
            'damages_kingdoms' => true,
            'kingdom_damage' => 1.25,
        ]), 12)->getSlotIds();

        $this->defendingKingdomCharacter->assignFactionSystem();

        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $this->assignFactionLoyaltyToKingdom($defendersKingdom->character, .05);

        $character = $this->character->getCharacter();

        $result = $this->attackWithItemService->useItemsOnKingdom($character, $defendersKingdom->refresh(), $slotIds);

        $this->assertEquals(200, $result['status']);

        $this->assertEquals('Dropped items on kingdom!', $result['message']);

        $this->assertNotEmpty(KingdomLog::where('character_id', $character->id)->get());

        Event::assertDispatched(GlobalMessageEvent::class);

        $defendersKingdom = $defendersKingdom->refresh();

        $this->assertEquals(0, $defendersKingdom->current_morale);
    }

    protected function assignFactionLoyaltyToKingdom(Character $character, float $kingdomItemDefenceBonus): Character
    {
        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $character->factions->first()->id,
            'character_id' => $character->id,
            'is_pledged' => true,
        ]);

        $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 5,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => $kingdomItemDefenceBonus,
        ]);

        return $character->refresh();
    }
}
