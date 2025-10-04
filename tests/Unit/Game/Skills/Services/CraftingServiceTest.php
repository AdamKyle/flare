<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\Item;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\SkillCheckService;
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

class CraftingServiceTest extends TestCase
{
    use CreateClass, CreateEvent, CreateFactionLoyalty, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateNpc, RefreshDatabase;

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
            'type' => ArmourTypes::SHIELD,
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
            'type' => WeaponTypes::WEAPON,
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
            'type' => SpellTypes::DAMAGE,
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
            'name' => CharacterClassValue::BLACKSMITH,
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
            'name' => CharacterClassValue::MERCHANT,
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
            'name' => CharacterClassValue::ARCANE_ALCHEMIST,
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
            'name' => CharacterClassValue::ARCANE_ALCHEMIST,
        ]))->assignSkill(
            $this->craftingSkill
        )->assignSkill(
            $spellCraftingSkill
        )->givePlayerLocation()->getCharacter();

        $spellToCraft = $this->createItem([
            'type' => SpellTypes::DAMAGE,
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
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $character = $character->refresh();

        $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type' => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $character = $character->refresh();

        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD, $character->gold);
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
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'name' => CharacterClassValue::BLACKSMITH,
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'name' => CharacterClassValue::BLACKSMITH,
        ]))->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]))->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'name' => CharacterClassValue::MERCHANT,
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'name' => CharacterClassValue::ARCANE_ALCHEMIST,
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'name' => CharacterClassValue::ARCANE_ALCHEMIST,
        ]))->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]))->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_crafts' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
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
}
