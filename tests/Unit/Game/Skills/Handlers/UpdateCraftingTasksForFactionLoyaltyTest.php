<?php

namespace Tests\Unit\Game\Skills\Handlers;

use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Handlers\UpdateCraftingTasksForFactionLoyalty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class UpdateCraftingTasksForFactionLoyaltyTest extends TestCase {

    use RefreshDatabase, CreateFactionLoyalty, CreateItem, CreateNpc, CreateMonster, CreateEvent;

    private ?UpdateCraftingTasksForFactionLoyalty $updateCraftingTasksForFactionLoyalty;

    public function setUp(): void {
        parent::setUp();

        $this->updateCraftingTasksForFactionLoyalty = resolve(UpdateCraftingTasksForFactionLoyalty::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->updateCraftingTasksForFactionLoyalty = null;
    }

    public function testNothingHappensWhenThePlayerIsNotPledgedToAFaction() {
        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        Event::fake();

        $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id'   => $character->factions->first(),
            'is_pledged'   => false
        ]);

        $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character->refresh(), $this->createItem());

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }

    public function testDoesNotHandleCraftingFactionLoyaltyWhenCharacterIsNotHelpingAnNpc() {
        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        Event::fake();

        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id'   => $character->factions->first(),
            'is_pledged'   => true
        ]);

        $npc = $this->createNpc();

        $this->createFactionLoyaltyNpc([
            'faction_loyalty_id'            => $factionLoyalty->id,
            'npc_id'                        => $npc->id,
            'current_level'                 => 1,
            'max_level'                     => 25,
            'next_level_fame'               => 1000,
            'currently_helping'             => false,
            'kingdom_item_defence_bonus'    => 0.002,
        ]);

        $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character->refresh(), $item);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }

    public function testDoesNotUpdateLoyaltyTasksWhenNoCraftingTaskFound() {
        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        Event::fake();

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
            'fame_tasks'                 => [['sample' => 'key']],
        ]);

        $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character->refresh(), $item);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }

    public function testDoesNotHandleCraftingFameWhenItemIsNotApartOfTheCraftingList() {
        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        Event::fake();

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
                'type'            => $item->crafting_type,
                'item_name'       => $item->affix_name,
                'item_id'         => 8674,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ]],
        ]);

        $character = $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character->refresh(), $item);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(0, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->factionLoyaltyNpcTasks->fame_tasks[0]['current_amount']);
    }

    public function testUpdatesTheCurrentAmountOnACraftingTaskButDoesNotLevelUpTheFame() {
        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        Event::fake();

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
                'type'            => $item->crafting_type,
                'item_name'    => $item->name,
                'item_id'      => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ]],
        ]);

        $character = $character->refresh();

        $character = $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character, $item);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(1, $character->factionLoyalties->first()
            ->factionLoyaltyNpcs
            ->first()
            ->factionLoyaltyNpcTasks
            ->fame_tasks[0]['current_amount']
        );
    }

    public function testUpdatesTheCurrentAmountOnACraftingTaskButDoesNotLevelUpTheFameWhenTheWeeklyEventIsRunning() {
        $item = $this->createItem();

        $this->createItem([
            'type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        Event::fake();

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
                'type'            => $item->crafting_type,
                'item_name'    => $item->name,
                'item_id'      => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ]],
        ]);

        $character = $character->refresh();

        $character = $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character, $item);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(1, $character->factionLoyalties->first()
            ->factionLoyaltyNpcs
            ->first()
            ->factionLoyaltyNpcTasks
            ->fame_tasks[0]['current_amount']
        );
    }

    public function testLevelUpFameFromCrafting() {

        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
            'xp_next' => 1000,
        ]);

        Event::fake();

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);
        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);
        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);
        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);
        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);

        $this->createItem([
            'crafting_type' => 'weapon',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'ring',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

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
            'next_level_fame'               => 1,
            'currently_helping'             => true,
            'kingdom_item_defence_bonus'    => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id'         => $factionLoyalty->id,
            'faction_loyalty_npc_id'     => $factionLoyaltyNpc->id,
            'fame_tasks'                 => [[
                'type'            => $item->crafting_type,
                'item_name'       => $item->name,
                'item_id'         => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character->refresh(), $item);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(2, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(1000000, $character->gold);
        $this->assertEquals(1000, $character->gold_dust);
        $this->assertEquals(100, $character->shards);

    }

    public function testLevelUpFameFromCraftingWhenTheEventIsRunning() {

        $item = $this->createItem();

        $this->createEvent([
            'type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
            'xp_next' => 1000,
        ]);

        Event::fake();

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);
        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);
        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);
        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);
        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);

        $this->createItem([
            'crafting_type' => 'weapon',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'ring',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

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
            'next_level_fame'               => 1,
            'currently_helping'             => true,
            'kingdom_item_defence_bonus'    => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id'         => $factionLoyalty->id,
            'faction_loyalty_npc_id'     => $factionLoyaltyNpc->id,
            'fame_tasks'                 => [[
                'type'            => $item->crafting_type,
                'item_name'       => $item->name,
                'item_id'         => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character->refresh(), $item);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(2, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(1000000, $character->gold);
        $this->assertEquals(1000, $character->gold_dust);
        $this->assertEquals(100, $character->shards);

    }

    public function testDoNotGiveMoreCurrenciesThenMaxAllowedForCraftingTasks() {
        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'xp_next' => 1000,
        ]);

        $character = $character->refresh();

        Event::fake();

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id
        ]);

        $this->createItem([
            'crafting_type' => 'weapon',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'ring',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

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
            'next_level_fame'               => 1,
            'currently_helping'             => true,
            'kingdom_item_defence_bonus'    => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id'         => $factionLoyalty->id,
            'faction_loyalty_npc_id'     => $factionLoyaltyNpc->id,
            'fame_tasks'                 => [[
                'type'            => $item->crafting_type,
                'item_name'    => $item->name,
                'item_id'      => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $character->refresh();

        $character = $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character, $item);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(2, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $character->gold);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
    }

    public function testDoNotAssignTasksForMaxLevelFame() {
        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'xp_next' => 1000,
        ]);

        $character = $character->refresh();

        Event::fake();

        $this->createItem([
            'crafting_type' => 'weapon',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'ring',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id'   => $character->factions->first(),
            'is_pledged'   => true
        ]);

        $npc = $this->createNpc();

        $factionLoyaltyNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id'            => $factionLoyalty->id,
            'npc_id'                        => $npc->id,
            'current_level'                 => 24,
            'max_level'                     => 25,
            'next_level_fame'               => 1,
            'currently_helping'             => true,
            'kingdom_item_defence_bonus'    => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id'         => $factionLoyalty->id,
            'faction_loyalty_npc_id'     => $factionLoyaltyNpc->id,
            'fame_tasks'                 => [[
                'type'            => $item->crafting_type,
                'item_name'       => $item->name,
                'item_id'         => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character->refresh(), $item);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(25, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $character->gold);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
        $this->assertCount(0, $character->factionLoyalties()->first()->factionLoyaltyNpcs->first()->factionLoyaltyNpcTasks->fame_tasks);
    }

    public function testCannotLevelFameAnyMore() {
        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
        ]);

        $character = $character->refresh();

        Event::fake();

        $this->createItem([
            'crafting_type' => 'weapon',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'armour',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'ring',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $this->createItem([
            'crafting_type' => 'spell',
            'skill_level_required' => 1,
            'skill_level_trivial' => 50,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id'   => $character->factions->first(),
            'is_pledged'   => true
        ]);

        $npc = $this->createNpc();

        $factionLoyaltyNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id'            => $factionLoyalty->id,
            'npc_id'                        => $npc->id,
            'current_level'                 => 25,
            'max_level'                     => 25,
            'next_level_fame'               => 1,
            'currently_helping'             => true,
            'kingdom_item_defence_bonus'    => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id'         => $factionLoyalty->id,
            'faction_loyalty_npc_id'     => $factionLoyaltyNpc->id,
            'fame_tasks'                 => [[
                'type'            => $item->crafting_type,
                'monster_name'    => $item->name,
                'monster_id'      => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->updateCraftingTasksForFactionLoyalty->handleCraftingTask($character->refresh(), $item);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(25, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(0, $character->gold);
        $this->assertEquals(0, $character->gold_dust);
        $this->assertEquals(0, $character->shards);
    }
}
