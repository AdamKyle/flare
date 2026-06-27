<?php

namespace Tests\Unit\Game\Quests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Validation\CanTravelToMap;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Quests\Handlers\NpcQuestsHandler;
use App\Game\Quests\Services\BuildQuestCacheService;
use App\Game\Quests\Services\QuestHandlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateMonsterCache;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRaid;

class QuestHandlerServiceTest extends TestCase
{
    use CreateEvent, CreateLocation, CreateMonster, CreateMonsterCache, CreateNpc, CreateQuest, CreateRaid, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_hand_in_quest_response_includes_completed_quest_in_completed_quests_list(): void
    {
        Event::fake();
        Queue::fake();

        $npc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_skill' => false,
            'gold_cost' => 100,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gold' => 200]);

        $result = resolve(QuestHandlerService::class)->handInQuest($character->refresh(), $quest);

        $this->assertEquals(200, $result['status']);
        $this->assertTrue(in_array($quest->id, $result['completed_quests']->toArray()));

        $request = CharacterBattleRewardRequest::firstOrFail();
        $this->assertSame(BattleRewardRequestPriority::FIRST, $request->priority);
        $this->assertSame(BattleRewardRequestSourceType::QUEST, $request->source_type);
        $this->assertSame(
            'quest:'.$character->id.':'.$quest->id,
            $request->source_id,
        );
        $this->assertSame([], $result['raid_quests']);
    }

    public function test_hand_in_quest_response_preserves_active_raid_quests(): void
    {
        Event::fake();
        Queue::fake();
        $npc = $this->createNpc();
        $raid = $this->createRaid([
            'raid_boss_id' => $this->createMonster()->id,
            'raid_boss_location_id' => $this->createLocation()->id,
        ]);
        $this->createEvent([
            'type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
        ]);
        Cache::put('raid-quests', [
            $raid->id => [
                ['id' => 987, 'name' => 'Active Raid Quest'],
            ],
        ]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_skill' => false,
            'gold_cost' => 100,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $character->update(['gold' => 200]);

        $result = resolve(QuestHandlerService::class)
            ->handInQuest($character->refresh(), $quest);

        $this->assertSame(
            [['id' => 987, 'name' => 'Active Raid Quest']],
            $result['raid_quests'],
        );
    }

    public function test_raid_quest_reward_uses_first_priority(): void
    {
        Event::fake();
        Queue::fake();
        $npc = $this->createNpc();
        $raid = $this->createRaid([
            'raid_boss_id' => $this->createMonster()->id,
            'raid_boss_location_id' => $this->createLocation()->id,
        ]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'raid_id' => $raid->id,
            'unlocks_skill' => false,
            'gold_cost' => 100,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $character->update(['gold' => 200]);

        resolve(QuestHandlerService::class)->handInQuest($character->refresh(), $quest);

        $request = CharacterBattleRewardRequest::firstOrFail();
        $this->assertSame(BattleRewardRequestPriority::FIRST, $request->priority);
        $this->assertSame(BattleRewardRequestSourceType::RAID_QUEST, $request->source_type);
        $this->assertSame(
            'raid_quest:'.$character->id.':'.$quest->id,
            $request->source_id,
        );
    }

    public function test_hand_in_quest_completes_quest_synchronously_without_requiring_refresh(): void
    {
        Event::fake();

        $npc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_skill' => false,
            'gold_cost' => 100,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gold' => 200]);

        resolve(QuestHandlerService::class)->handInQuest($character->refresh(), $quest);

        $this->assertEquals(1, $character->fresh()->questsCompleted()->where('quest_id', $quest->id)->count());
    }

    public function test_should_bail_on_quest_returns_false_when_character_has_assisting_npc_at_required_fame_level(): void
    {
        $questNpc = $this->createNpc();
        $assistingNpc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $questNpc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'assisting_npc_id' => $assistingNpc->id,
            'required_fame_level' => 3,
            'required_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'unlocks_skill' => false,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $faction = $character->factions()->first();

        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);

        FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $assistingNpc->id,
            'current_level' => 5,
        ]);

        $result = resolve(QuestHandlerService::class)->shouldBailOnQuest($character->refresh(), $quest);

        $this->assertFalse($result);
    }

    public function test_should_bail_on_quest_returns_true_when_character_has_fame_on_quest_giver_but_not_assisting_npc(): void
    {
        $questNpc = $this->createNpc();
        $assistingNpc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $questNpc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'assisting_npc_id' => $assistingNpc->id,
            'required_fame_level' => 3,
            'required_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'unlocks_skill' => false,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $faction = $character->factions()->first();

        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);

        FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $questNpc->id,
            'current_level' => 5,
        ]);

        $result = resolve(QuestHandlerService::class)->shouldBailOnQuest($character->refresh(), $quest);

        $this->assertTrue($result);
    }

    public function test_should_bail_on_quest_returns_true_when_character_is_below_required_fame_level_for_assisting_npc(): void
    {
        $questNpc = $this->createNpc();
        $assistingNpc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $questNpc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'assisting_npc_id' => $assistingNpc->id,
            'required_fame_level' => 10,
            'required_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'unlocks_skill' => false,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->getCharacter();

        $faction = $character->factions()->first();

        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $faction->id,
        ]);

        FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $assistingNpc->id,
            'current_level' => 3,
        ]);

        $result = resolve(QuestHandlerService::class)->shouldBailOnQuest($character->refresh(), $quest);

        $this->assertTrue($result);
    }

    public function test_should_bail_on_quest_returns_false_when_quest_has_no_assisting_npc_requirement(): void
    {
        $questNpc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $questNpc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'assisting_npc_id' => null,
            'required_fame_level' => null,
            'required_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'unlocks_skill' => false,
            'gold_cost' => 0,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $result = resolve(QuestHandlerService::class)->shouldBailOnQuest($character->refresh(), $quest);

        $this->assertFalse($result);
    }

    public function test_move_character_updates_map_id_when_npc_is_on_different_map(): void
    {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $npcMap = $this->createGameMap(['name' => 'Labyrinth', 'default' => false]);
        $npc = $this->createNpc(['game_map_id' => $npcMap->id, 'x_position' => 32, 'y_position' => 32]);

        $this->createMonsterCache();

        $canTravelToMap = Mockery::mock(CanTravelToMap::class);
        $canTravelToMap->shouldReceive('canTravel')->once()->andReturn(true);

        $mapTileValue = Mockery::mock(MapTileValue::class);
        $mapTileValue->shouldReceive('setUp')->once()->andReturnSelf();
        $mapTileValue->shouldReceive('canWalk')->once()->andReturn(true);

        $service = new QuestHandlerService(
            resolve(NpcQuestsHandler::class),
            $canTravelToMap,
            $mapTileValue,
            resolve(BuildQuestCacheService::class),
            resolve(BattleRewardProcessingQueueManager::class),
        );

        $result = $service->moveCharacter($character, $npc);

        $this->assertInstanceOf(Character::class, $result);
        $this->assertEquals($npcMap->id, $result->map->game_map_id);
    }

    public function test_move_character_updates_xy_to_npc_location_when_npc_is_on_different_map(): void
    {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $npcMap = $this->createGameMap(['name' => 'Labyrinth', 'default' => false]);
        $npc = $this->createNpc(['game_map_id' => $npcMap->id, 'x_position' => 64, 'y_position' => 96]);

        $this->createMonsterCache();

        $canTravelToMap = Mockery::mock(CanTravelToMap::class);
        $canTravelToMap->shouldReceive('canTravel')->once()->andReturn(true);

        $mapTileValue = Mockery::mock(MapTileValue::class);
        $mapTileValue->shouldReceive('setUp')->once()->andReturnSelf();
        $mapTileValue->shouldReceive('canWalk')->once()->andReturn(true);

        $service = new QuestHandlerService(
            resolve(NpcQuestsHandler::class),
            $canTravelToMap,
            $mapTileValue,
            resolve(BuildQuestCacheService::class),
            resolve(BattleRewardProcessingQueueManager::class),
        );

        $result = $service->moveCharacter($character, $npc);

        $this->assertInstanceOf(Character::class, $result);
        $this->assertEquals(64, $result->map->character_position_x);
        $this->assertEquals(96, $result->map->character_position_y);
    }

    public function test_move_character_fires_update_map_after_final_position_is_saved(): void
    {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $npcMap = $this->createGameMap(['name' => 'Labyrinth', 'default' => false]);
        $npc = $this->createNpc(['game_map_id' => $npcMap->id, 'x_position' => 64, 'y_position' => 96]);

        $this->createMonsterCache();

        $canTravelToMap = Mockery::mock(CanTravelToMap::class);
        $canTravelToMap->shouldReceive('canTravel')->andReturn(true);

        $mapTileValue = Mockery::mock(MapTileValue::class);
        $mapTileValue->shouldReceive('setUp')->andReturnSelf();
        $mapTileValue->shouldReceive('canWalk')->andReturn(true);

        $service = new QuestHandlerService(
            resolve(NpcQuestsHandler::class),
            $canTravelToMap,
            $mapTileValue,
            resolve(BuildQuestCacheService::class),
            resolve(BattleRewardProcessingQueueManager::class),
        );

        $result = $service->moveCharacter($character, $npc);

        $this->assertEquals(64, $result->map->character_position_x);
        $this->assertEquals(96, $result->map->character_position_y);
        Event::assertDispatched(UpdateMap::class);
    }

    public function test_move_character_on_same_map_updates_xy_and_fires_update_map(): void
    {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id,
            'x_position' => 64,
            'y_position' => 96,
        ]);

        $this->createMonsterCache();

        $mapTileValue = Mockery::mock(MapTileValue::class);
        $mapTileValue->shouldReceive('setUp')->once()->andReturnSelf();
        $mapTileValue->shouldReceive('canWalk')->once()->andReturn(true);

        $service = new QuestHandlerService(
            resolve(NpcQuestsHandler::class),
            resolve(CanTravelToMap::class),
            $mapTileValue,
            resolve(BuildQuestCacheService::class),
            resolve(BattleRewardProcessingQueueManager::class),
        );

        $result = $service->moveCharacter($character, $npc);

        $this->assertInstanceOf(Character::class, $result);
        $this->assertEquals(64, $result->map->character_position_x);
        $this->assertEquals(96, $result->map->character_position_y);
        Event::assertDispatched(UpdateMap::class);
    }

    public function test_move_character_dispatches_attack_types_cache_builder_when_map_changes(): void
    {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $npcMap = $this->createGameMap(['name' => 'Labyrinth', 'default' => false]);
        $npc = $this->createNpc(['game_map_id' => $npcMap->id, 'x_position' => 64, 'y_position' => 96]);

        $this->createMonsterCache();

        $canTravelToMap = Mockery::mock(CanTravelToMap::class);
        $canTravelToMap->shouldReceive('canTravel')->andReturn(true);

        $mapTileValue = Mockery::mock(MapTileValue::class);
        $mapTileValue->shouldReceive('setUp')->andReturnSelf();
        $mapTileValue->shouldReceive('canWalk')->andReturn(true);

        $service = new QuestHandlerService(
            resolve(NpcQuestsHandler::class),
            $canTravelToMap,
            $mapTileValue,
            resolve(BuildQuestCacheService::class),
            resolve(BattleRewardProcessingQueueManager::class),
        );

        $service->moveCharacter($character, $npc);

        Queue::assertPushed(CharacterAttackTypesCacheBuilder::class);
    }

    public function test_move_character_does_not_dispatch_attack_types_cache_builder_when_npc_is_on_same_map(): void
    {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id,
            'x_position' => 64,
            'y_position' => 96,
        ]);

        $this->createMonsterCache();

        $mapTileValue = Mockery::mock(MapTileValue::class);
        $mapTileValue->shouldReceive('setUp')->once()->andReturnSelf();
        $mapTileValue->shouldReceive('canWalk')->once()->andReturn(true);

        $service = new QuestHandlerService(
            resolve(NpcQuestsHandler::class),
            resolve(CanTravelToMap::class),
            $mapTileValue,
            resolve(BuildQuestCacheService::class),
            resolve(BattleRewardProcessingQueueManager::class),
        );

        $service->moveCharacter($character, $npc);

        Queue::assertNotPushed(CharacterAttackTypesCacheBuilder::class);
    }
}
