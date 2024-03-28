<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\ItemUsabilityType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\SkillCheckService;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Values\SkillTypeValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\AlchemyService;
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

class CraftingServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateClass, CreateGameSkill, CreateFactionLoyalty, CreateNpc, CreateEvent, CreateGlobalEventGoal;

    private ?CharacterFactory $character;

    private ?CraftingService $craftingService;

    private ?Item $craftingItem;

    private ?GameSkill $craftingSkill;

    public function setUp(): void {
        parent::setUp();

        $this->craftingSkill = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]);

        $this->character = (new CharacterFactory())->createBaseCharacter()->assignSkill(
            $this->craftingSkill
        )->givePlayerLocation();

        $this->craftingService = resolve(CraftingService::class);

        $this->craftingItem = $this->createItem([
            'cost'                 => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial'  => 100,
            'crafting_type'        => 'weapon',
            'type'                 => 'weapon',
            'can_craft'            => true,
            'default_position'     => 'hammer',
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character       = null;
        $this->craftingService = null;
        $this->craftingItem    = null;
        $this->craftingSkill   = null;
    }

    public function testFetchCraftableItems() {
        $character = $this->character->getCharacter();

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'hammer',
        ]);

        $this->assertNotEmpty($result);
    }

    public function testFetchCraftableItemsForArmour() {
        $character = $this->character->assignSkill($this->createGameSkill([
            'name' => 'Armour Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]))->getCharacter();

        $this->createItem([
            'type'                 => ArmourTypes::SHIELD,
            'crafting_type'        => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial'  => 100,
            'can_craft'            => true,
        ]);

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'armour',
        ]);

        $this->assertNotEmpty($result);
    }

    public function testFetchCraftableItemsForRegularWeapon() {
        $character = $this->character->getCharacter();

        $this->createItem([
            'type'                 => WeaponTypes::WEAPON,
            'crafting_type'        => 'weapon',
            'skill_level_required' => 1,
            'skill_level_trivial'  => 100,
            'can_craft'            => true,
        ]);

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'weapon',
        ]);

        $this->assertNotEmpty($result);
    }

    public function testFetchCraftableItemsForRegularSpells() {
        $character = $this->character->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]))->getCharacter();

        $this->createItem([
            'type'                 => SpellTypes::DAMAGE,
            'crafting_type'        => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial'  => 100,
            'can_craft'            => true,
        ]);

        $result = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => 'spell',
        ]);

        $this->assertNotEmpty($result);
    }

    public function testFetchCraftableItemsAsBlackSmith() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::BLACKSMITH
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

    public function testFetchCraftableItemsAsMerhant() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT
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

    public function testFetchCraftableItemsAsArcaneAlchemist() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::ARCANE_ALCHEMIST
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

    public function testFetchCraftableItemsAsArcaneAlchemistWhenCraftingSpells() {
        Event::fake();

        $spellCraftingSkill = $this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::ARCANE_ALCHEMIST
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

    public function testFailToCraftForItemThatDoesNotExist() {
        Event::fake();

        $character = $this->character->getCharacter();

        $result = $this->craftingService->craft($character, [
            'item_to_craft' => 10,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertFalse($result);
    }

    public function testCannotAffordToCraftItem() {
        Event::fake();

        $character = $this->character->getCharacter();

        $result = $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('not_enough_gold');
        });

        $this->assertFalse($result);
    }

    public function testItemToHardToCraft() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $character->refresh();

        $this->craftingItem->update([
            'skill_level_required' => 500,
        ]);

        $result = $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->refresh()->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('to_hard_to_craft');
        });

        $this->assertFalse($result);
    }

    public function testItemToEasyToCraft() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $character->refresh();

        $this->craftingItem->update([
            'skill_level_trivial' => -10,
        ]);

        $result = $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->refresh()->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('to_easy_to_craft');
        });

        $this->assertTrue($result);
    }

    public function testGeneralCraft() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $character->refresh();

        $this->craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $character = $character->refresh();

        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD, $character->gold);
    }

    public function testGeneralCraftInventoryIsFull() {
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
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('inventory_full');
        });
    }

    public function  testFailToCraft() {
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
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $character->refresh();

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('failed_to_craft');
        });
    }

    public function testSucceedInCrafting() {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $character->refresh();

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);
    }

    public function testCraftAsBlackSmith() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::BLACKSMITH
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCraftSpellAsBlackSmith() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::BLACKSMITH
        ]))->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]))->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->createItem([
                'type'                 => 'spell-damage',
                'crafting_type'        => 'spell-damage',
                'skill_level_required' => 1,
                'skill_level_trivial'  => 10,
                'cost'                 => 10,
            ])->id,
            'type'          => 'spell',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Blacksmith, your crafting timeout is increased by 25% for spell crafting.';
        });
    }

    public function testCraftAsMerchant() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCraftSpellAsArcaneAlchemist() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::ARCANE_ALCHEMIST
        ]))->assignSkill($this->craftingSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $character->refresh();

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type'          => 'hammer',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCraftSpellAsArcaneAlchemsit() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::ARCANE_ALCHEMIST
        ]))->assignSkill($this->createGameSkill([
            'name' => 'Spell Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]))->givePlayerLocation()->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $craftingService->craft($character, [
            'item_to_craft' => $this->createItem([
                'type'                 => 'spell-damage',
                'crafting_type'        => 'spell-damage',
                'skill_level_required' => 1,
                'skill_level_trivial'  => 10,
                'cost'                 => 10,
            ])->id,
            'type'          => 'spell',
            'craft_for_npc' => false,
            'craft_for_event' => false,
        ]);

        $this->assertCount(1, $character->inventory->slots);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Arcane Alchemist, your crafting timeout is reduced by 15% for spell crafting.';
        });
    }

    public function testFetchCharacterWeaponCraftingXP() {
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

    public function testItemIsGivenToNpcWhenDoingFactionLoyaltyCrafting() {


        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->assignSkill($this->craftingSkill)
            ->givePlayerLocation()
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $craftingService = $this->app->make(CraftingService::class);

        $character = $character->refresh();

        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id'   => $character->factions->first(),
            'is_pledged'   => true
        ]);

        $npc = $this->createNpc();

        $factionLoyaltyNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id'            => $factionLoyalty->id,
            'npc_id'                        => $npc->id,
            'current_level'                 => 1,
            'max_level'                     => 25,
            'next_level_fame'               => 1000,
            'currently_helping'             => true,
            'kingdom_item_defence_bonus'    => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id'         => $factionLoyalty->id,
            'faction_loyalty_npc_id'     => $factionLoyaltyNpc->id,
            'fame_tasks'                 => [[
                'type'            => $this->craftingItem->crafting_type,
                'item_name'    => $this->craftingItem->name,
                'item_id'      => $this->craftingItem->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ]],
        ]);

        $character = $character->refresh();

        $craftingService->craft($character, [
            'item_to_craft' => $this->craftingItem->id,
            'type'          => 'hammer',
            'craft_for_npc' => true,
            'craft_for_event' => false,
        ]);

        $character = $character->refresh();

        $this->assertCount(0, $character->inventory->slots);

        $this->assertEquals(1, $character->factionLoyalties->first()
            ->factionLoyaltyNpcs
            ->first()
            ->factionLoyaltyNpcTasks
            ->fame_tasks[0]['current_amount']
        );
    }

    public function testCraftWhileParticipatingInEventGoal() {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $craftingService = $this->app->make(CraftingService::class);

        $this->createEvent([
            'type'                    => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $this->createGlobalEventGoal([
            'event_type'                  => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_crafts'                  => 100,
            'reward_every'                => 10,
            'next_reward_at'              => 10,
            'item_specialty_type_reward'  => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'            => false,
            'should_be_mythic'            => true,
        ]);

        $character = $this->character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $character->refresh();

        $craftingService->craft($character, [
            'item_to_craft'   => $this->craftingItem->id,
            'type'            => 'hammer',
            'craft_for_npc'   => false,
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
}
