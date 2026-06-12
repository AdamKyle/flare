<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Service\AttackWithItemsService;
use App\Game\Kingdoms\Values\KingdomMaxValue;
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
        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]);
        $character = $this->character->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 3,
        ]);

        $charactersOwnKingdom = $this->createKingdomForCharacter($this->character);

        $result = $this->attackWithItemService->useItemsOnKingdom($character, $charactersOwnKingdom, [$slot->id]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You cannot attack your own kingdoms.', $result['message']);
    }

    public function testCannotAttackProtectedKingdom()
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]);
        $character = $this->character->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 3,
        ]);

        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $defendersKingdom->update([
            'protected_until' => now()->addDays(7)
        ]);

        $defendersKingdom = $defendersKingdom->refresh();

        $result = $this->attackWithItemService->useItemsOnKingdom($character, $defendersKingdom, [$slot->id]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('This kingdom is currently under The Creators protection and cannot be targeted right now.', $result['message']);
    }

    public function testCannotAttackKingdomNotOnTheSamePlane()
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]);
        $character = $this->character->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 3,
        ]);

        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $differentMap = $this->createGameMap(['name' => 'far away place']);

        $defendersKingdom->update([
            'game_map_id' => $differentMap->id
        ]);

        $defendersKingdom = $defendersKingdom->refresh();

        $result = $this->attackWithItemService->useItemsOnKingdom($character, $defendersKingdom, [$slot->id]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You need to be on the same plane as the kingdom you want to attack with items.', $result['message']);
    }

    public function testDropItemOnKingdom()
    {

        Event::fake();

        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 0
        ]);

        $this->defendingKingdomCharacter->assignFactionSystem();

        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $this->assignFactionLoyaltyToKingdom($defendersKingdom->character, .05);

        $character = $this->character->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $result = $this->attackWithItemService->useItemsOnKingdom($character, $defendersKingdom->refresh(), [$slot->id]);

        $this->assertEquals(200, $result['status']);

        $this->assertEquals('Dropped items on kingdom!', $result['message']);

        $this->assertNotEmpty(KingdomLog::where('character_id', $character->id)->get());

        $defendersKingdom = $defendersKingdom->refresh();

        $this->assertGreaterThan(0, $defendersKingdom->current_morale);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertEquals(1, $slot->refresh()->amount);
    }

    public function testDropItemsOnKingdomAndMoraleBecomesZero()
    {

        Event::fake();

        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 1.25,
        ]);

        $this->defendingKingdomCharacter->assignFactionSystem();

        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $this->assignFactionLoyaltyToKingdom($defendersKingdom->character, .05);

        $character = $this->character->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 12,
        ]);

        $result = $this->attackWithItemService->useItemsOnKingdom(
            $character,
            $defendersKingdom->refresh(),
            [$slot->id, $slot->id, $slot->id, $slot->id, $slot->id, $slot->id, $slot->id, $slot->id, $slot->id, $slot->id, $slot->id, $slot->id]
        );

        $this->assertEquals(200, $result['status']);

        $this->assertEquals('Dropped items on kingdom!', $result['message']);

        $this->assertNotEmpty(KingdomLog::where('character_id', $character->id)->get());

        Event::assertDispatched(GlobalMessageEvent::class);

        $defendersKingdom = $defendersKingdom->refresh();

        $this->assertEquals(0, $defendersKingdom->current_morale);
        $this->assertEquals(0, AlchemyBagSlot::where('id', $slot->id)->count());
    }

    public function testCannotUseMoreKingdomDamageItemsThanAlchemyStackContains(): void
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]);
        $character = $this->character->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);
        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $result = $this->attackWithItemService->useItemsOnKingdom(
            $character,
            $defendersKingdom,
            [$slot->id, $slot->id, $slot->id]
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals(2, $slot->refresh()->amount);
    }

    public function testCannotUseAnotherCharactersAlchemyBagSlotOnKingdom(): void
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]);
        $character = $this->character->getCharacter();
        $otherCharacter = $this->defendingKingdomCharacter->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);
        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);

        $result = $this->attackWithItemService->useItemsOnKingdom($character, $defendersKingdom, [$slot->id]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals(2, $slot->refresh()->amount);
    }

    public function testKingdomItemDamageIsClampedToZeroWithoutIncreasingBuildingsOrUnits(): void
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.04,
        ]);
        $character = $this->character->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);
        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);
        $defendersKingdom->update([
            'treasury' => KingdomMaxValue::MAX_TREASURY,
            'gold_bars' => KingdomMaxValue::MAX_GOLD_BARS,
        ]);
        $building = $defendersKingdom->buildings->first();
        $unit = $defendersKingdom->units->first();

        $result = $this->attackWithItemService->useItemsOnKingdom(
            $character,
            $defendersKingdom->refresh(),
            [$slot->id]
        );

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(0, AlchemyBagSlot::where('id', $slot->id)->count());
        $this->assertEquals(100, $building->refresh()->current_durability);
        $this->assertEquals(1000, $unit->refresh()->amount);
        $this->assertEquals(0.0, KingdomLog::where('character_id', $character->id)->latest('id')->value('item_damage'));
        Event::assertDispatched(function (GlobalMessageEvent $event) {
            return str_contains($event->message, 'doing a total of: 0% damage.');
        });
    }

    public function testKingdomItemDamageUsesDefencePointsThenItemResistanceAndLogsBreakdown(): void
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 8.04,
        ]);
        $character = $this->character->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->defendingKingdomCharacter->assignFactionSystem();
        $defendersKingdom = $this->createKingdomForCharacter($this->defendingKingdomCharacter);
        $this->assignFactionLoyaltyToKingdom($defendersKingdom->character, .19);
        $defendersKingdom->update([
            'treasury' => KingdomMaxValue::MAX_TREASURY,
            'gold_bars' => 740,
        ]);

        $result = $this->attackWithItemService->useItemsOnKingdom(
            $character,
            $defendersKingdom->refresh(),
            [$slot->id]
        );

        $log = KingdomLog::where('character_id', $character->id)->latest('id')->first();
        $breakdown = $log->additional_details['item_damage_breakdown'];

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(0, AlchemyBagSlot::where('id', $slot->id)->count());
        $this->assertEqualsWithDelta(8.04, $breakdown['raw_item_damage'], 0.00001);
        $this->assertEqualsWithDelta(1.74, $breakdown['kingdom_defence'], 0.00001);
        $this->assertEqualsWithDelta(6.30, $breakdown['damage_after_defence'], 0.00001);
        $this->assertEqualsWithDelta(.95, $breakdown['item_resistance'], 0.00001);
        $this->assertEqualsWithDelta(.315, $breakdown['final_damage'], 0.00001);
        $this->assertEqualsWithDelta(.1575, $breakdown['building_damage'], 0.00001);
        $this->assertEqualsWithDelta(.1575, $breakdown['unit_damage'], 0.00001);
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
