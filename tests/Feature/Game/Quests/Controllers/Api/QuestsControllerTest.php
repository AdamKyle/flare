<?php

namespace Tests\Feature\Game\Quests\Controllers\Api;

use App\Flare\Values\AutomationType;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class QuestsControllerTest extends TestCase
{
    use CreateItem, CreateLocation, CreateNpc, CreateQuest, RefreshDatabase;

    public function test_regular_quest_list_can_be_fetched_during_exploration(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/quests/'.$character->id);

        $response->assertOk();
        $this->assertArrayHasKey('quests', $response->json());
    }

    public function test_regular_quest_info_can_be_fetched_during_exploration(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $quest = $this->createQuest([
            'npc_id' => $this->createNpc()->id,
        ]);
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/quest/'.$quest->id.'/'.$character->id);

        $response->assertOk();
        $response->assertJsonPath('id', $quest->id);
    }

    public function test_regular_quest_hand_in_is_blocked_during_exploration(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $quest = $this->createQuest([
            'npc_id' => $this->createNpc()->id,
        ]);
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/quest/'.$quest->id.'/hand-in-quest/'.$character->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot do that while Exploration automation is running. Cancel it first.',
        ]);
    }

    public function test_regular_quest_hand_in_is_blocked_during_delve(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $quest = $this->createQuest([
            'npc_id' => $this->createNpc()->id,
        ]);
        $characterFactory->assignAutomation([
            'type' => AutomationType::DELVE,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/quest/'.$quest->id.'/hand-in-quest/'.$character->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot do that while Delve automation is running. Cancel it first.',
        ]);
    }

    public function test_regular_quest_can_be_handed_in_when_character_is_already_at_npc_location(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $characterMap = $character->map;
        $npc = $this->createNpc([
            'game_map_id' => $characterMap->game_map_id,
            'x_position' => $characterMap->character_position_x,
            'y_position' => $characterMap->character_position_y,
        ]);
        $quest = $this->createQuest([
            'name' => 'A Quest At The NPC',
            'npc_id' => $npc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
            'gold_cost' => 0,
            'copper_coin_cost' => 0,
            'reincarnated_times' => 0,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'required_faction_level' => null,
            'assisting_npc_id' => null,
            'required_fame_level' => null,
            'parent_quest_id' => null,
            'required_quest_id' => null,
            'parent_chain_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'reward_item' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_gold' => null,
            'reward_xp' => null,
            'unlocks_skill' => false,
            'unlocks_skill_type' => null,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/quest/'.$quest->id.'/hand-in-quest/'.$character->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $response->assertJsonPath(
            'message',
            'You completed the quest: '.$quest->name.'. Above is the updated story for the quest.'
        );
        $this->assertContains($quest->id, $response->json('completed_quests'));
        $updatedMap = $character->refresh()->map;
        $this->assertSame($characterMap->game_map_id, $updatedMap->game_map_id);
        $this->assertSame($characterMap->character_position_x, $updatedMap->character_position_x);
        $this->assertSame($characterMap->character_position_y, $updatedMap->character_position_y);
    }

    public function test_regular_quest_moves_character_to_npc_and_hands_in_quest(): void
    {
        Event::fake();
        Queue::fake();
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->once()->andReturnSelf();
                $mock->shouldReceive('canWalk')->once()->andReturn(true);
            })
        );

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $characterMap = $character->map;
        Cache::put('monsters', [$characterMap->gameMap->name => []]);
        $npc = $this->createNpc([
            'game_map_id' => $characterMap->game_map_id,
            'x_position' => $characterMap->character_position_x + 16,
            'y_position' => $characterMap->character_position_y + 16,
        ]);
        $quest = $this->createQuest([
            'name' => 'A Quest Away From The NPC',
            'npc_id' => $npc->id,
            'item_id' => null,
            'secondary_required_item' => null,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
            'gold_cost' => 0,
            'copper_coin_cost' => 0,
            'reincarnated_times' => 0,
            'access_to_map_id' => null,
            'faction_game_map_id' => null,
            'required_faction_level' => null,
            'assisting_npc_id' => null,
            'required_fame_level' => null,
            'parent_quest_id' => null,
            'required_quest_id' => null,
            'parent_chain_quest_id' => null,
            'required_quest_chain' => null,
            'only_for_event' => null,
            'reward_item' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_gold' => null,
            'reward_xp' => null,
            'unlocks_skill' => false,
            'unlocks_skill_type' => null,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/quest/'.$quest->id.'/hand-in-quest/'.$character->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertContains($quest->id, $response->json('completed_quests'));
        $updatedMap = $character->refresh()->map;
        $this->assertSame($npc->x_position, $updatedMap->character_position_x);
        $this->assertSame($npc->y_position, $updatedMap->character_position_y);
        $this->assertSame($characterMap->game_map_id, $updatedMap->game_map_id);
        $this->assertSame(
            1,
            $character->questsCompleted()->where('quest_id', $quest->id)->count()
        );
    }

    public function test_quest_item_drop_location_payload_includes_delve_fields_for_delve_location(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $npc = $this->createNpc();
        $location = $this->createLocation([
            'hours_to_drop' => 2,
            'minutes_between_delve_fights' => 5,
        ]);
        $item = $this->createItem(['type' => 'quest', 'drop_location_id' => $location->id]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $item->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/quest/'.$quest->id.'/'.$character->id);

        $response->assertOk();
        $this->assertEquals(2, $response->json('item.drop_location.hours_to_drop'));
        $this->assertEquals(5, $response->json('item.drop_location.minutes_between_delve_fights'));
    }

    public function test_quest_item_drop_location_payload_has_no_delve_fields_for_normal_special_location(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $npc = $this->createNpc();
        $location = $this->createLocation();
        $item = $this->createItem(['type' => 'quest', 'drop_location_id' => $location->id]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $item->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/quest/'.$quest->id.'/'.$character->id);

        $response->assertOk();
        $hoursToDropValue = $response->json('item.drop_location.hours_to_drop');
        $this->assertTrue(
            $hoursToDropValue === null || $hoursToDropValue === 0,
            'Normal location should not have hours_to_drop > 0'
        );
    }

    public function test_regular_quest_hand_in_is_blocked_during_faction_loyalty(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $quest = $this->createQuest([
            'npc_id' => $this->createNpc()->id,
        ]);
        $characterFactory->assignAutomation([
            'type' => AutomationType::FACTION_LOYALTY,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/quest/'.$quest->id.'/hand-in-quest/'.$character->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot do that while Faction Loyalty automation is running. Cancel it first.',
        ]);
    }
}
