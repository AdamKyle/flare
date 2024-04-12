<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class FactionLoyaltyBountyHandlerTest extends TestCase {

    use RefreshDatabase, CreateMonster, CreateFactionLoyalty, CreateNpc, CreateItem, CreateEvent;

    private ?FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler;

    public function setUp(): void {
        parent::setUp();

        $this->factionLoyaltyBountyHandler = resolve(FactionLoyaltyBountyHandler::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->factionLoyaltyBountyHandler  = null;
    }

    public function testDoesNotHandleBountyWhenAutomationIsRunning() {

        $monster = $this->createMonster();

        $character = (new CharacterFactory())->createBaseCharacter()
                                             ->givePlayerLocation()
                                             ->assignAutomation([
                                                'monster_id' => $monster->id
                                             ])
                                             ->getCharacter();
        Event::fake();

        $this->factionLoyaltyBountyHandler->handleBounty($character, $monster);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }

    public function testDoesNotHandleBountyWhenCharacterIsNotPledgedToAFaction() {
        $monster = $this->createMonster();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
            ->assignFactionSystem()
            ->getCharacter();

        Event::fake();

        $this->createFactionLoyalty([
            'character_id' => $character->id,
            'faction_id'   => $character->factions->first(),
            'is_pledged'   => false
        ]);

        $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }

    public function testDoesNotHandleBountyWhenCharacterIsNotHelpingAnNpc() {
        $monster = $this->createMonster();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
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

        $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }

    public function testDoesNotUpdateLoyaltyTasksWhenNoBountyTaskFound() {
        $monster = $this->createMonster();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
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

        $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
    }

    public function testDoesNotHandleBountyWhenMonsterIsNotApartOfTheBountyList() {
        $monster = $this->createMonster();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
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
                'type'            => 'bounty',
                'monster_name'    => 'sample',
                'monster_id'      => 99999,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ]],
        ]);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(0, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->factionLoyaltyNpcTasks->fame_tasks[0]['current_amount']);
    }

    public function testUpdatesTheCurrentAmountOnABountyButDoesNotLevelUpTheFame() {
        $monster = $this->createMonster();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
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
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ]],
        ]);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(1, $character->factionLoyalties->first()
            ->factionLoyaltyNpcs
            ->first()
            ->factionLoyaltyNpcTasks
            ->fame_tasks[0]['current_amount']
        );
    }

    public function testUpdatesTheCurrentAmountOnABountyButDoesNotLevelUpTheFameDuringTheWeeklyEvent() {
        $monster = $this->createMonster();

        $this->createEvent([
            'type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
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
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ]],
        ]);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(2, $character->factionLoyalties->first()
            ->factionLoyaltyNpcs
            ->first()
            ->factionLoyaltyNpcTasks
            ->fame_tasks[0]['current_amount']
        );
    }

    public function testLevelUpFame() {
        $monster = $this->createMonster();

        $this->createMonster([
            'game_map_id' => $monster->game_map_id
        ]);

        $this->createMonster([
            'game_map_id' => $monster->game_map_id
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
        ]);

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
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(2, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(1000000, $character->gold);
        $this->assertEquals(1000, $character->gold_dust);
        $this->assertEquals(100, $character->shards);
    }

    public function testLevelUpFameDuringTheWeeklyEvent() {
        $monster = $this->createMonster();

        $this->createEvent([
            'type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
        ]);

        $this->createMonster([
            'game_map_id' => $monster->game_map_id
        ]);

        $this->createMonster([
            'game_map_id' => $monster->game_map_id
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
        ]);

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
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(2, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(1000000, $character->gold);
        $this->assertEquals(1000, $character->gold_dust);
        $this->assertEquals(100, $character->shards);
    }

    public function testDoNotGiveMoreCurrenciesThenMaxAllowed() {
        $monster = $this->createMonster();

        $this->createMonster([
            'game_map_id' => $monster->game_map_id
        ]);

        $this->createMonster([
            'game_map_id' => $monster->game_map_id
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
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
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(2, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $character->gold);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
    }

    public function testDoNotAssignTasksForMaxLevelFame() {
        $monster = $this->createMonster();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
            ->assignFactionSystem()
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
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
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(25, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $character->gold);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
        $this->assertCount(0, $character->factionLoyalties()->first()->factionLoyaltyNpcs->first()->factionLoyaltyNpcTasks->fame_tasks);
    }

    public function testCannotLevelFameAnyMore() {
        $monster = $this->createMonster();

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $monster->gameMap)
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
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 200000,
            ]],
        ]);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character->refresh(), $monster);

        Event::assertNotDispatched(ServerMessageEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(25, $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->current_level);
        $this->assertEquals(0, $character->gold);
        $this->assertEquals(0, $character->gold_dust);
        $this->assertEquals(0, $character->shards);
    }
}
