<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\Item;
use App\Game\Character\Values\CharacterClass;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\CraftingMessageMode;
use App\Game\Skills\Values\CraftingSkillGroup;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateScheduledEvent;

class CraftingServiceTest extends TestCase
{
    use CreateClass, CreateEvent, CreateFactionLoyalty, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateNpc, CreateScheduledEvent, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CraftingService $craftingService;

    private ?Item $craftingItem;

    private ?GameSkill $craftingSkill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->craftingSkill = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->craftingSkill
        )->givePlayerLocation();

        $this->craftingService = resolve(CraftingService::class);

        $this->craftingItem = $this->createItem([
            'cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->craftingService = null;
        $this->craftingItem = null;
        $this->craftingSkill = null;
    }

    public function test_fetch_craftable_items()
    {
        $character = $this->character->getCharacter();

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'hammer',
        ]);

        $this->assertNotEmpty($result);
    }

    public function test_fetch_craftable_items_for_armour()
    {
        $character = $this->character->assignSkill($this->createGameSkill([
            'name' => 'Armour Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]))->getCharacter();

        $this->createItem([
            'type' => ArmourType::SHIELD->value,
            'crafting_type' => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'can_craft' => true,
        ]);

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'armour',
        ]);

        $this->assertNotEmpty($result);
    }

    public function test_fetch_craftable_items_for_regular_weapon()
    {
        $character = $this->character->getCharacter();

        $this->createItem([
            'type' => ItemType::WEAPON->value,
            'crafting_type' => 'weapon',
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'can_craft' => true,
        ]);

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'weapon',
        ]);

        $this->assertNotEmpty($result);
    }

    public function test_fetch_craftable_items_for_regular_spells()
    {
        $character = $this->character->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]))->getCharacter();

        $this->createItem([
            'type' => ItemType::SPELL_DAMAGE->value,
            'crafting_type' => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'can_craft' => true,
        ]);

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'spell',
        ]);

        $this->assertNotEmpty($result);
    }

    public function test_fetch_craftable_items_as_black_smith()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::BLACKSMITH->value,
        ]))->assignSkill(
            $this->craftingSkill
        )->givePlayerLocation()->getCharacter();

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'hammer',
        ]);

        $this->assertNotEmpty($result);
        $this->assertNotEquals($result[0]->cost, $this->craftingItem->cost);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_fetch_craftable_items_as_merhant()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::MERCHANT->value,
        ]))->assignSkill(
            $this->craftingSkill
        )->givePlayerLocation()->getCharacter();

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'hammer',
        ]);

        $this->assertNotEmpty($result);
        $this->assertNotEquals($result[0]->cost, $this->craftingItem->cost);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_fetch_craftable_items_as_arcane_alchemist()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::ARCANE_ALCHEMIST->value,
        ]))->assignSkill(
            $this->craftingSkill
        )->givePlayerLocation()->getCharacter();

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'hammer',
        ]);

        $this->assertNotEmpty($result);
        $this->assertEquals($result[0]->cost, $this->craftingItem->cost);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_fetch_craftable_items_as_arcane_alchemist_when_crafting_spells()
    {
        Event::fake();

        $spellCraftingSkill = $this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::ARCANE_ALCHEMIST->value,
        ]))->assignSkill(
            $this->craftingSkill
        )->assignSkill(
            $spellCraftingSkill
        )->givePlayerLocation()->getCharacter();

        $spellToCraft = $this->createItem([
            'type' => ItemType::SPELL_DAMAGE->value,
            'skill_level_required' => 1,
            'skill_level_trivial' => 10,
            'crafting_type' => 'spell',
            'can_craft' => true,
        ]);

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'spell',
        ]);

        $this->assertNotEmpty($result);
        $this->assertLessThan($spellToCraft->cost, $result[0]->cost);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_fail_to_craft_for_item_that_does_not_exist()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $result = $this->craftingService->craft($character, [
            'item_to_craft' => 10,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertFalse($result);
    }

    public function test_cannot_afford_to_craft_item()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $result = $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CharacterMessageTypes::NOT_ENOUGH_GOLD);
        });

        $this->assertFalse($result);
    }

    public function test_item_to_hard_to_craft()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $character->refresh();

        $this->craftingItem->update([
            'skill_level_required' => 500,
        ]);

        $result = $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->refresh()->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::TO_HARD_TO_CRAFT);
        });

        $this->assertFalse($result);
    }

    public function test_item_to_easy_to_craft()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $character->refresh();

        $this->craftingItem->update([
            'skill_level_trivial' => -10,
        ]);

        $result = $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->refresh()->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::TO_EASY_TO_CRAFT);
        });

        $this->assertTrue($result);
    }

    public function test_general_craft()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $character->refresh();

        $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $character = $character->refresh();

        $this->assertLessThan(CurrencyLimit::MAX_GOLD, $character->gold);
    }

    public function test_general_craft_inventory_is_full()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CharacterMessageTypes::INVENTORY_IS_FULL);
        });
    }

    public function test_fail_to_craft()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $character->refresh();

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::FAILED_TO_CRAFT);
        });
    }

    public function test_succeed_in_crafting()
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $character->refresh();

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);
    }

    public function test_craft_as_black_smith()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::BLACKSMITH->value,
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_craft_spell_as_black_smith()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::BLACKSMITH->value,
        ]))->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]))->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->createItem([
                'type' => 'spell-damage',
                'crafting_type' => 'spell-damage',
                'skill_level_required' => 1,
                'skill_level_trivial' => 10,
                'cost' => 10,
            ])->id,
            'type' => 'spell',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Blacksmith, your crafting timeout is increased by 25% for spell crafting.';
        });
    }

    public function test_craft_as_merchant()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::MERCHANT->value,
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_craft_spell_as_arcane_alchemist()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::ARCANE_ALCHEMIST->value,
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $character->refresh();

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_craft_spell_as_arcane_alchemsit()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::ARCANE_ALCHEMIST->value,
        ]))->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]))->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->createItem([
                'type' => 'spell-damage',
                'crafting_type' => 'spell-damage',
                'skill_level_required' => 1,
                'skill_level_trivial' => 10,
                'cost' => 10,
            ])->id,
            'type' => 'spell',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Arcane Alchemist, your crafting timeout is reduced by 15% for spell crafting.';
        });
    }

    public function test_fetch_character_weapon_crafting_xp()
    {
        $character = $this->character->getCharacter();

        $weaponCraftingXpData = $this->craftingService->getCraftingXP($character, 'hammer');

        $weaponCraftingSkill = $character->skills()->where('game_skill_id', $this->craftingSkill->id)->first();

        $expected = [
            'current_xp' => 0,
            'next_level_xp' => $weaponCraftingSkill->xp_max,
            'skill_name' => $weaponCraftingSkill->baseSkill->name,
            'level' => $weaponCraftingSkill->level,
        ];

        $this->assertEquals($weaponCraftingXpData, $expected);
    }

    public function test_fetch_character_inventory_count()
    {
        $character = $this->character->getCharacter();

        $inventoryCount = $this->craftingService->getInventoryCount($character);

        $expected = [
            'current_count' => $character->getInventoryCount(),
            'max_inventory' => $character->inventory_max,
        ];

        $this->assertEquals($expected, $inventoryCount);
    }

    public function test_item_is_given_to_npc_when_doing_faction_loyalty_crafting()
    {

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->assignSkill($this->craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $character = $character->refresh();

        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id' => $character->factions->first(),
            'is_pledged' => true,
        ]);

        $npc = $this->createNpc();

        $factionLoyaltyNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 1,
            'max_level' => 25,
            'next_level_fame' => 1000,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'fame_tasks' => [[
                'type' => $this->craftingItem->crafting_type,
                'item_name' => $this->craftingItem->name,
                'item_id' => $this->craftingItem->id,
                'required_amount' => rand(10, 50),
                'current_amount' => 0,
            ]],
        ]);

        $character = $character->refresh();

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => true,
            'craft_for_event' => false,
        ]);

        $character = $character->refresh();

        $this->assertCount(0, $character->inventory->slots);

        $this->assertEquals(
            1,
            $character->factionLoyalties->first()
                ->factionLoyaltyNpcs
                ->first()
                ->factionLoyaltyNpcTasks
                ->fame_tasks[0]['current_amount']
        );
    }

    public function test_item_is_not_given_to_npc_when_doing_faction_loyalty_crafting()
    {

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->assignSkill($this->craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $character = $character->refresh();

        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id' => $character->factions->first(),
            'is_pledged' => false,
        ]);

        $npc = $this->createNpc();

        $factionLoyaltyNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 1,
            'max_level' => 25,
            'next_level_fame' => 1000,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'fame_tasks' => [[
                'type' => $this->craftingItem->crafting_type,
                'item_name' => $this->craftingItem->name,
                'item_id' => $this->craftingItem->id,
                'required_amount' => rand(10, 50),
                'current_amount' => 0,
            ]],
        ]);

        $character = $character->refresh();

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => true,
            'craft_for_event' => false,
        ]);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);

        $this->assertEquals(
            0,
            $character->factionLoyalties->first()
                ->factionLoyaltyNpcs
                ->first()
                ->factionLoyaltyNpcTasks
                ->fame_tasks[0]['current_amount']
        );
    }

    public function test_craft_while_participating_in_event_goal()
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $craftingService = $this->app->make(CraftingService::class);

        $schedule = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => true,
        ]);

        $character = $character->refresh();

        $this->assertCount(0, $character->inventory->slots);

        $globalEventCraftingInventory = GlobalEventCraftingInventory::where('character_id', $character->id)->first();

        $this->assertNotNull($globalEventCraftingInventory);

        $globalEventCraftingInventorySlot = GlobalEventCraftingInventorySlot::where('global_event_crafting_inventory_id', $globalEventCraftingInventory->id)->first();

        $this->assertNotNull($globalEventCraftingInventorySlot);

        $this->assertEquals($this->craftingItem->id, $globalEventCraftingInventorySlot->item_id);
    }

    public function test_craft_while_participating_in_event_goal_when_current_event_goal_is_not_craft()
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $craftingService = $this->app->make(CraftingService::class);

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::BATTLE,
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $character->refresh();

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => true,
        ]);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);

        $globalEventCraftingInventory = GlobalEventCraftingInventory::where('character_id', $character->id)->first();

        $this->assertNull($globalEventCraftingInventory);
    }

    public function test_craft_for_batch_standard_mode_sends_the_standard_success_messages()
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);
        $character = $character->refresh();
        $this->craftingItem->update(['skill_level_trivial' => -10]);

        $this->craftingService->craftForBatch($character, $this->craftingItem->refresh(), 'hammer', CraftingMessageMode::STANDARD);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::TO_EASY_TO_CRAFT);
        });
        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::CRAFTED, $this->craftingItem->name);
        });
    }

    public function test_craft_for_batch_batch_crafting_mode_suppresses_the_too_easy_and_crafted_messages()
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);
        $character = $character->refresh();
        $this->craftingItem->update(['skill_level_trivial' => -10]);

        $result = $this->craftingService->craftForBatch($character, $this->craftingItem->refresh(), 'hammer', CraftingMessageMode::BATCH_CRAFTING);

        $this->assertTrue($result['success']);
        Event::assertNotDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::TO_EASY_TO_CRAFT);
        });
        Event::assertNotDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::CRAFTED, $this->craftingItem->name);
        });
    }

    public function test_craft_for_batch_batch_crafting_mode_still_sends_the_failed_roll_message()
    {
        Event::fake();
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);
        $character = $character->refresh();

        $result = $this->app->make(CraftingService::class)->craftForBatch($character, $this->craftingItem->refresh(), 'hammer', CraftingMessageMode::BATCH_CRAFTING);

        $this->assertFalse($result['success']);
        $this->assertSame('failed_roll', $result['reason']);
        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::FAILED_TO_CRAFT);
        });
    }

    public function test_craft_for_batch_reports_not_enough_gold_without_charging_gold()
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $result = $this->craftingService->craftForBatch($character, $this->craftingItem->refresh(), 'hammer', CraftingMessageMode::BATCH_CRAFTING);

        $this->assertFalse($result['success']);
        $this->assertSame('not_enough_gold', $result['reason']);
        $this->assertSame(0, $character->refresh()->gold);
    }

    public function test_craft_for_batch_reports_destination_failed_when_the_destination_creator_returns_null()
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);
        $character = $character->refresh();
        $this->craftingItem->update(['skill_level_trivial' => -10]);

        $result = $this->craftingService->craftForBatch(
            $character,
            $this->craftingItem->refresh(),
            'hammer',
            CraftingMessageMode::BATCH_CRAFTING,
            fn () => null,
        );

        $this->assertFalse($result['success']);
        $this->assertSame('destination_failed', $result['reason']);
        $this->assertNull($result['destination']);
    }

    public function test_is_skill_maxed_is_true_when_the_skill_level_reached_its_max_level()
    {
        $character = $this->character->getCharacter();
        $character->skills()->where('game_skill_id', $this->craftingSkill->id)->update(['level' => $this->craftingSkill->max_level]);
        $skill = $this->craftingService->getCraftingSkillForAutomation($character->refresh(), 'weapon');

        $result = $this->craftingService->isSkillMaxed($skill);

        $this->assertTrue($result);
    }

    public function test_is_skill_maxed_is_false_when_the_skill_can_still_gain_levels()
    {
        $character = $this->character->getCharacter();
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'weapon');

        $result = $this->craftingService->isSkillMaxed($skill);

        $this->assertFalse($result);
    }

    public function test_fetch_meaningful_experience_candidates_returns_the_strongest_first_for_weapon_group()
    {
        $character = $this->character->getCharacter();
        $character->skills()->where('game_skill_id', $this->craftingSkill->id)->update(['level' => 5]);
        $character = $character->refresh();
        $weakerItem = $this->createItem(['cost' => 10, 'skill_level_required' => 2, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'weapon', 'can_craft' => true, 'default_position' => 'hammer']);
        $strongerItem = $this->createItem(['cost' => 10, 'skill_level_required' => 4, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'weapon', 'can_craft' => true, 'default_position' => 'hammer']);
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'weapon');

        $result = $this->craftingService->fetchMeaningfulExperienceCandidates($skill, CraftingSkillGroup::WEAPON);

        $this->assertSame($strongerItem->id, $result->first()?->id);
        $this->assertTrue($result->pluck('id')->contains($weakerItem->id));
    }

    public function test_fetch_meaningful_experience_candidates_excludes_items_that_are_now_trivial_for_the_skill()
    {
        $character = $this->character->getCharacter();
        $this->craftingItem->update(['skill_level_trivial' => 0]);
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'weapon');

        $result = $this->craftingService->fetchMeaningfulExperienceCandidates($skill, CraftingSkillGroup::WEAPON);

        $this->assertTrue($result->isEmpty());
    }

    public function test_fetch_meaningful_experience_candidates_narrows_by_armour_group()
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = $this->character->assignSkill($armourCrafting)->getCharacter();

        $bodyItem = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);
        $helmetItem = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'helmet', 'can_craft' => true, 'default_position' => 'helmet']);
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'armour');

        $result = $this->craftingService->fetchMeaningfulExperienceCandidates($skill, CraftingSkillGroup::ARMOUR);

        $this->assertTrue($result->pluck('id')->contains($bodyItem->id));
        $this->assertTrue($result->pluck('id')->contains($helmetItem->id));
    }

    public function test_fetch_meaningful_experience_candidates_narrows_by_weapon_group_across_default_position_and_type(): void
    {
        $character = $this->character->getCharacter();
        $hammerItem = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'weapon', 'can_craft' => true, 'default_position' => 'hammer']);
        $swordItem = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'weapon');

        $result = $this->craftingService->fetchMeaningfulExperienceCandidates($skill, CraftingSkillGroup::WEAPON);

        $this->assertTrue($result->pluck('id')->contains($hammerItem->id));
        $this->assertTrue($result->pluck('id')->contains($swordItem->id));
    }

    public function test_find_inexpensive_craftable_item_returns_the_cheapest_candidate()
    {
        $character = $this->character->getCharacter();
        $cheapItem = $this->createItem(['cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'weapon', 'can_craft' => true, 'default_position' => 'hammer']);
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'weapon');

        $result = $this->craftingService->findInexpensiveCraftableItem($skill, 'weapon');

        $this->assertSame($cheapItem->id, $result?->id);
    }

    public function test_find_inexpensive_craftable_item_narrows_by_item_type_when_provided()
    {
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($spellCrafting, 10, false)->getCharacter();

        $this->createItem(['cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'spell', 'type' => 'spell-healing', 'can_craft' => true, 'default_position' => 'spell-healing']);
        $matchingItem = $this->createItem(['cost' => 50, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'spell', 'type' => 'spell-damage', 'can_craft' => true, 'default_position' => 'spell-damage']);
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'spell');

        $result = $this->craftingService->findInexpensiveCraftableItem($skill, 'spell', 'spell-damage');

        $this->assertSame($matchingItem->id, $result?->id);
    }

    public function test_find_inexpensive_craftable_item_returns_null_when_nothing_is_craftable()
    {
        $character = $this->character->getCharacter();
        Item::query()->delete();
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'weapon');

        $result = $this->craftingService->findInexpensiveCraftableItem($skill, 'weapon');

        $this->assertNull($result);
    }

    public function test_find_craftable_item_for_automation_returns_the_exact_currently_craftable_item()
    {
        $character = $this->character->getCharacter();

        $result = $this->craftingService->findCraftableItemForAutomation($character, $this->craftingItem->id);

        $this->assertSame($this->craftingItem->id, $result?->id);
    }

    public function test_find_craftable_item_for_automation_returns_null_for_a_nonexistent_item()
    {
        $character = $this->character->getCharacter();

        $result = $this->craftingService->findCraftableItemForAutomation($character, 999999);

        $this->assertNull($result);
    }

    public function test_find_craftable_item_for_automation_returns_null_when_the_character_cannot_craft_the_item()
    {
        $armourItem = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);
        $character = $this->character->getCharacter();

        $result = $this->craftingService->findCraftableItemForAutomation($character, $armourItem->id);

        $this->assertNull($result);
    }

    public function test_find_craftable_item_for_automation_respects_the_real_crafting_discipline_for_armour()
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = $this->character->assignSkill($armourCrafting)->getCharacter();

        $armourItem = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);

        $result = $this->craftingService->findCraftableItemForAutomation($character, $armourItem->id);

        $this->assertSame($armourItem->id, $result?->id);
    }

    public function test_find_craftable_item_for_automation_resolves_a_shield_through_the_armour_discipline()
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = $this->character->assignSkill($armourCrafting)->getCharacter();

        $shield = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'shield', 'can_craft' => true, 'default_position' => 'shield']);

        $result = $this->craftingService->findCraftableItemForAutomation($character, $shield->id);

        $this->assertSame($shield->id, $result?->id);
    }

    public function test_find_inexpensive_craftable_weapon_for_automation_returns_the_cheapest_across_every_weapon_subtype()
    {
        $character = $this->character->getCharacter();
        $expensiveSword = $this->createItem(['cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);
        $cheapDagger = $this->createItem(['cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'dagger', 'can_craft' => true, 'default_position' => 'dagger']);
        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'weapon');

        $result = $this->craftingService->findInexpensiveCraftableWeaponForAutomation($skill);

        $this->assertSame($cheapDagger->id, $result?->id);
        $this->assertNotSame($expensiveSword->id, $result?->id);
    }

    public function test_craft_for_batch_reports_zero_xp_gained_on_a_failed_roll()
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1000);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );
        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);

        $result = $this->app->make(CraftingService::class)->craftForBatch($character->refresh(), $this->craftingItem->refresh(), 'hammer', CraftingMessageMode::BATCH_CRAFTING);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['xp_gained']);
    }

    public function test_craft_for_batch_reports_zero_xp_gained_for_a_trivial_success()
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);
        $this->craftingItem->update(['skill_level_trivial' => -10]);

        $result = $this->craftingService->craftForBatch($character->refresh(), $this->craftingItem->refresh(), 'hammer', CraftingMessageMode::BATCH_CRAFTING);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['xp_gained']);
    }

    public function test_craft_for_batch_reports_the_factual_xp_actually_awarded_on_a_successful_roll()
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );
        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);
        $skill = $character->refresh()->skills()->where('game_skill_id', $this->craftingSkill->id)->first();
        $xpBefore = $skill->xp;

        $result = $this->app->make(CraftingService::class)->craftForBatch($character->refresh(), $this->craftingItem->refresh(), 'hammer', CraftingMessageMode::BATCH_CRAFTING);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['xp_gained']);
        $this->assertSame($xpBefore + $result['xp_gained'], $skill->refresh()->xp);
    }

    public function test_fetch_paginated_craftable_items_applies_the_item_type_filter()
    {
        $character = $this->character->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]))->getCharacter();

        $this->createItem([
            'name' => 'Searing Bolt',
            'type' => 'spell-damage',
            'crafting_type' => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'can_craft' => true,
        ]);

        $this->createItem([
            'name' => 'Mending Light',
            'type' => 'spell-healing',
            'crafting_type' => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'can_craft' => true,
        ]);

        $result = $this->craftingService->fetchPaginatedCraftableItems(
            $character,
            ['crafting_type' => 'spell'],
            15,
            1,
            '',
            '',
            'spell-damage'
        );

        $this->assertCount(1, $result['data']);
        $this->assertSame('Searing Bolt', $result['data'][0]['preview']['name']);
    }

    public function test_fetch_paginated_craftable_items_still_applies_the_armour_subtype_filter()
    {
        $character = $this->character->assignSkill($this->createGameSkill([
            'name' => 'Armour Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
        ]))->getCharacter();

        $this->createItem([
            'name' => 'Reinforced Plate Body',
            'type' => ArmourType::BODY->value,
            'crafting_type' => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'can_craft' => true,
        ]);

        $this->createItem([
            'name' => 'Reinforced Plate Helmet',
            'type' => ArmourType::HELMET->value,
            'crafting_type' => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'can_craft' => true,
        ]);

        $result = $this->craftingService->fetchPaginatedCraftableItems(
            $character,
            ['crafting_type' => 'armour'],
            15,
            1,
            '',
            ArmourType::BODY->value
        );

        $this->assertCount(1, $result['data']);
        $this->assertSame('Reinforced Plate Body', $result['data'][0]['preview']['name']);
    }

    public function test_fetch_best_craftable_items_by_type_for_automation_chooses_the_highest_required_level_per_type()
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = $this->character->assignSkill($armourCrafting)->getCharacter();

        $lowerBody = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);
        $higherBody = $this->createItem(['cost' => 10, 'skill_level_required' => 3, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);
        $lowerHelmet = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'helmet', 'can_craft' => true, 'default_position' => 'helmet']);
        $higherHelmet = $this->createItem(['cost' => 10, 'skill_level_required' => 3, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'helmet', 'can_craft' => true, 'default_position' => 'helmet']);

        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'armour');
        $skill->update(['level' => 5]);

        $result = $this->craftingService->fetchBestCraftableItemsByTypeForAutomation($skill->refresh(), 'armour', ['body', 'helmet']);

        $this->assertSame($higherBody->id, $result->get('body')->id);
        $this->assertSame($higherHelmet->id, $result->get('helmet')->id);
        $this->assertNotSame($lowerBody->id, $result->get('body')->id);
        $this->assertNotSame($lowerHelmet->id, $result->get('helmet')->id);
    }

    public function test_fetch_best_craftable_items_by_type_for_automation_breaks_ties_with_the_lower_id()
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = $this->character->assignSkill($armourCrafting)->getCharacter();

        $firstBody = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);
        $secondBody = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);

        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'armour');

        $result = $this->craftingService->fetchBestCraftableItemsByTypeForAutomation($skill, 'armour', ['body']);

        $expectedId = min($firstBody->id, $secondBody->id);

        $this->assertSame($expectedId, $result->get('body')->id);
    }

    public function test_fetch_best_craftable_items_by_type_for_automation_omits_a_type_with_no_candidate()
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = $this->character->assignSkill($armourCrafting)->getCharacter();

        $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);

        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'armour');

        $result = $this->craftingService->fetchBestCraftableItemsByTypeForAutomation($skill, 'armour', ['body', 'helmet']);

        $this->assertTrue($result->has('body'));
        $this->assertFalse($result->has('helmet'));
    }

    public function test_fetch_best_craftable_items_by_type_for_automation_excludes_items_beyond_the_characters_skill_level()
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);

        $character = $this->character->assignSkill($armourCrafting)->getCharacter();

        $tooHighBody = $this->createItem(['cost' => 10, 'skill_level_required' => 500, 'skill_level_trivial' => 600, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);
        $craftableBody = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'body', 'can_craft' => true, 'default_position' => 'body']);

        $skill = $this->craftingService->getCraftingSkillForAutomation($character, 'armour');

        $result = $this->craftingService->fetchBestCraftableItemsByTypeForAutomation($skill, 'armour', ['body']);

        $this->assertSame($craftableBody->id, $result->get('body')->id);
        $this->assertNotSame($tooHighBody->id, $result->get('body')->id);
    }

    public function test_find_best_craftable_weapon_for_automation_returns_the_highest_actually_craftable_sword(): void
    {
        $character = $this->character->getCharacter();
        $character->skills()->where('game_skill_id', $this->craftingSkill->id)->update(['level' => 5]);
        $character = $character->refresh();

        $lowerSword = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);
        $higherSword = $this->createItem(['cost' => 10, 'skill_level_required' => 4, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);
        $inaccessibleSword = $this->createItem(['cost' => 10, 'skill_level_required' => 500, 'skill_level_trivial' => 600, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);

        $result = $this->craftingService->findBestCraftableWeaponForAutomation($character, 'sword');

        $this->assertSame($higherSword->id, $result?->id);
        $this->assertNotSame($lowerSword->id, $result?->id);
        $this->assertNotSame($inaccessibleSword->id, $result?->id);
    }

    public function test_find_best_craftable_weapon_for_automation_breaks_ties_with_the_lower_id(): void
    {
        $character = $this->character->getCharacter();

        $firstSword = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);
        $secondSword = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'sword', 'can_craft' => true, 'default_position' => 'sword']);

        $result = $this->craftingService->findBestCraftableWeaponForAutomation($character, 'sword');

        $expectedId = min($firstSword->id, $secondSword->id);

        $this->assertSame($expectedId, $result?->id);
    }

    public function test_find_best_craftable_weapon_for_automation_matches_a_two_handed_weapon_by_default_position(): void
    {
        $character = $this->character->getCharacter();

        $bow = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'weapon', 'type' => 'weapon', 'can_craft' => true, 'default_position' => 'bow']);

        $result = $this->craftingService->findBestCraftableWeaponForAutomation($character, 'bow');

        $this->assertSame($bow->id, $result?->id);
    }

    public function test_find_best_craftable_shield_for_automation_returns_the_highest_actually_craftable_shield(): void
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value]);
        $character = $this->character->assignSkill($armourCrafting, 5)->getCharacter();

        $lowerShield = $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'shield', 'can_craft' => true, 'default_position' => 'shield']);
        $higherShield = $this->createItem(['cost' => 10, 'skill_level_required' => 4, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'shield', 'can_craft' => true, 'default_position' => 'shield']);
        $inaccessibleShield = $this->createItem(['cost' => 10, 'skill_level_required' => 500, 'skill_level_trivial' => 600, 'crafting_type' => 'armour', 'type' => 'shield', 'can_craft' => true, 'default_position' => 'shield']);

        $result = $this->craftingService->findBestCraftableShieldForAutomation($character);

        $this->assertSame($higherShield->id, $result?->id);
        $this->assertNotSame($lowerShield->id, $result?->id);
        $this->assertNotSame($inaccessibleShield->id, $result?->id);
    }

    public function test_find_best_craftable_shield_for_automation_returns_null_without_an_armour_crafting_skill(): void
    {
        $character = $this->character->getCharacter();

        $this->createItem(['cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 100, 'crafting_type' => 'armour', 'type' => 'shield', 'can_craft' => true, 'default_position' => 'shield']);

        $result = $this->craftingService->findBestCraftableShieldForAutomation($character);

        $this->assertNull($result);
    }
}
