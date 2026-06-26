<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;

class DelveExplorationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Character $character;

    private Location $location;

    private Monster $monster;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->location = Location::factory()->create([
            'x' => $this->character->map->character_position_x,
            'y' => $this->character->map->character_position_y,
            'game_map_id' => $this->character->map->game_map_id,
            'type' => LocationType::CAVE_OF_MEMORIES,
            'minutes_between_delve_fights' => 5,
        ]);

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
                'only_for_location_type' => LocationType::CAVE_OF_MEMORIES,
                'is_celestial_entity' => false,
                'is_raid_monster' => false,
                'is_raid_boss' => false,
                'raid_special_attack_type' => null,
            ])
            ->getMonster();
    }

    public function testBeginStartsDelve(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 5,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Delve has started child. Let us see how long you last shall we? (Max delve time is 8 hours.)', $jsonData['message']);
    }

    public function testStopStopsDelve(): void
    {
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        DelveExploration::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/stop', [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBeginReturns422WhenAttackTypeIsInvalid(): void
    {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => 'invalid',
                'pack_size' => 5,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Invalid attack type was selected. Please select from the drop down.', $jsonData['message']);
    }

    public function testBeginReturns422WhenAutomationIsAlreadyRunning(): void
    {
        Queue::fake();
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 5,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You cannot do that while Delve automation is running. Cancel it first.', $jsonData['message']);
    }

    public function testBeginReturns422WhenCharacterIsNotOnCaveOfMemoriesLocation(): void
    {
        Queue::fake();
        Event::fake();

        $this->location->delete();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 5,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You may only delve in locations that allow such an action child.', $jsonData['message']);
    }

    public function testStatusReturnsInactiveWhenNoDelveRunning(): void
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->json('active'));
    }

    public function testStatusReturnsCompletedDelveUntilDismissed(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'ended_reason' => 'player_stopped',
            'panel_dismissed_at' => null,
        ]);

        DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'outcome' => 'survived',
            'pack_size' => 5,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->json('active'));
        $this->assertTrue($response->json('completed'));
        $this->assertSame('player_stopped', $response->json('reason'));
        $this->assertEquals(1, DelveLog::where('delve_exploration_id', $delve->id)->where('pack_size', 5)->count());
    }

    public function testDismissHidesCompletedDelveWithoutDeletingLogs(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'ended_reason' => 'died',
            'panel_dismissed_at' => null,
        ]);

        $log = DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'outcome' => 'died',
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/dismiss', [
                '_token' => csrf_token(),
            ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->json('active'));
        $this->assertFalse($response->json('completed'));
        $this->assertNotNull($delve->refresh()->panel_dismissed_at);
        $this->assertEquals(1, DelveLog::where('id', $log->id)->where('delve_exploration_id', $delve->id)->count());
    }

    public function testNewDelveRunShowsAfterPreviousCompletedPanelWasDismissed(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
            'ended_reason' => 'player_stopped',
            'panel_dismissed_at' => now()->subMinutes(30),
        ]);

        $active = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
            'panel_dismissed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->json('active'));
        $this->assertFalse($response->json('completed'));
        $this->assertSame($active->monster_id, $response->json('current_foe.id'));
    }

    public function testStatusReturnsActiveDataWhenDelveIsRunning(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->json('active'));
        $this->assertArrayHasKey('elapsed_seconds', $response->json());
        $this->assertArrayHasKey('increase_percentage', $response->json());
        $this->assertArrayHasKey('quest_items', $response->json());
        $this->assertArrayHasKey('reward_checkpoints', $response->json());
    }

    public function testStatusRewardCheckpointsReachFirstCheckpointByDefault(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $checkpoints = $response->json('reward_checkpoints');
        $this->assertNotEmpty($checkpoints);
        $this->assertTrue($checkpoints[0]['reached']);
        $this->assertFalse($checkpoints[1]['reached']);
    }

    public function testStatusReturnsQuestItemDropCountdownFromHoursToDrop(): void
    {
        $this->location->update(['hours_to_drop' => 2]);

        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, $response->json('quest_item_drop_hours_required'));
        $this->assertFalse($response->json('quest_item_drop_available'));
        $this->assertNotNull($response->json('quest_item_drop_seconds_remaining'));
    }

    public function testStatusReturnsQuestItemDropAvailableWhenElapsedTimeExceedsHoursToDrop(): void
    {
        $this->location->update(['hours_to_drop' => 1]);

        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now()->subHours(2),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->json('quest_item_drop_available'));
        $this->assertSame(0, $response->json('quest_item_drop_seconds_remaining'));
    }

    public function testStatusReturnsNullCountdownWhenNoLocationHasHoursToDrop(): void
    {
        $this->location->update(['hours_to_drop' => null]);

        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($response->json('quest_item_drop_hours_required'));
        $this->assertFalse($response->json('quest_item_drop_available'));
        $this->assertSame([], $response->json('quest_items'));
    }

    public function testStatusQuestItemsUseLocationDropRulesNotMonsterQuestItemId(): void
    {
        $this->location->update(['hours_to_drop' => 1]);

        $item = Item::factory()->create([
            'type' => 'quest',
            'drop_location_id' => $this->location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $questItems = $response->json('quest_items');
        $this->assertCount(1, $questItems);
        $this->assertSame($item->id, $questItems[0]['id']);
    }

    public function testStatusMarksHaveWhenItemIsInInventory(): void
    {
        $this->location->update(['hours_to_drop' => 1]);

        $item = Item::factory()->create([
            'type' => 'quest',
            'drop_location_id' => $this->location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $inventory = Inventory::where('character_id', $this->character->id)->first();

        InventorySlot::create([
            'inventory_id' => $inventory->id,
            'item_id' => $item->id,
        ]);

        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $questItems = $response->json('quest_items');
        $this->assertCount(1, $questItems);
        $this->assertTrue($questItems[0]['have']);
        $this->assertNotNull($questItems[0]['slot_id']);
    }

    public function testStatusMarksHadWhenItemUsedAsQuestRequiredItem(): void
    {
        $this->location->update(['hours_to_drop' => 1]);

        $item = Item::factory()->create([
            'type' => 'quest',
            'drop_location_id' => $this->location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $npc = Npc::factory()->create();
        $quest = Quest::factory()->create(['item_id' => $item->id, 'npc_id' => $npc->id]);

        QuestsCompleted::create([
            'character_id' => $this->character->id,
            'quest_id' => $quest->id,
        ]);

        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $questItems = $response->json('quest_items');
        $this->assertCount(1, $questItems);
        $this->assertTrue($questItems[0]['had']);
    }

    public function testStatusMarksHadWhenItemUsedAsQuestSecondaryRequiredItem(): void
    {
        $this->location->update(['hours_to_drop' => 1]);

        $item = Item::factory()->create([
            'type' => 'quest',
            'drop_location_id' => $this->location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $npc = Npc::factory()->create();
        $quest = Quest::factory()->create([
            'npc_id' => $npc->id,
            'item_id' => null,
            'secondary_required_item' => $item->id,
        ]);

        QuestsCompleted::create([
            'character_id' => $this->character->id,
            'quest_id' => $quest->id,
        ]);

        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $questItems = $response->json('quest_items');
        $this->assertCount(1, $questItems);
        $this->assertTrue($questItems[0]['had']);
    }

    public function testStatusDoesNotMarkHadWhenNoMatchingCompletedQuestExists(): void
    {
        $this->location->update(['hours_to_drop' => 1]);

        $item = Item::factory()->create([
            'type' => 'quest',
            'drop_location_id' => $this->location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $questItems = $response->json('quest_items');
        $this->assertCount(1, $questItems);
        $this->assertFalse($questItems[0]['had']);
    }

    public function testStatusReturnsAvailableEnemyStatsFromActiveMonster(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('enemy_stats_available', $response->json());
        $this->assertTrue($response->json('enemy_stats_available'));
    }

    public function testQuestItemDetailReturnsItemDataForQuestItem(): void
    {
        $item = Item::factory()->create([
            'type' => 'quest',
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/quest-item/' . $item->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('item', $response->json());
        $this->assertArrayHasKey('description', $response->json('item'));
    }

    public function testQuestItemDetailReturnsItemNameInResponse(): void
    {
        $item = Item::factory()->create([
            'type' => 'quest',
            'name' => 'Test Quest Item',
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/quest-item/' . $item->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Test Quest Item', $response->json('item.name'));
    }

    public function testQuestItemDetailReturns422ForNonQuestItem(): void
    {
        $item = Item::factory()->create([
            'type' => 'weapon',
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/quest-item/' . $item->id);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testQuestItemDetailReturns404ForMissingItem(): void
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/quest-item/99999999');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testQuestItemDetailRequiresAuthentication(): void
    {
        $item = Item::factory()->create([
            'type' => 'quest',
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $response = $this->call('GET', '/api/delve/' . $this->character->id . '/quest-item/' . $item->id);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testQuestItemDetailDoesNotRequireOwnership(): void
    {
        $item = Item::factory()->create([
            'type' => 'quest',
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/quest-item/' . $item->id);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testQuestItemDetailResponseDoesNotContainAdminOnlyFields(): void
    {
        $item = Item::factory()->create([
            'type' => 'quest',
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/quest-item/' . $item->id);

        $this->assertSame(200, $response->getStatusCode());
        $itemData = $response->json('item');
        $this->assertArrayNotHasKey('raw_damage_modifier', $itemData);
        $this->assertArrayNotHasKey('character_id', $itemData);
    }

    public function testStatusCurrentFoeSourceIsActiveDelveFallbackWhenNoLogExists(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('active_delve', $response->json('current_foe.source'));
    }

    public function testStatusCurrentFoeNameFromActiveDelveFallback(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->monster->name, $response->json('current_foe.name'));
    }

    public function testStatusCurrentFoeSourceIsLatestLogWhenLogHasFightData(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'fight_data' => [
                'monster' => ['id' => $this->monster->id, 'name' => $this->monster->name, 'str' => 100],
            ],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('latest_log', $response->json('current_foe.source'));
    }

    public function testStatusCurrentFoeNameFromLatestLogFightData(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'fight_data' => [
                'monster' => ['id' => $this->monster->id, 'name' => 'Shadow Wraith', 'str' => 200],
            ],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Shadow Wraith', $response->json('current_foe.name'));
    }

    public function testStatusCurrentFoePackSizeFromLog(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'pack_size' => 5,
            'fight_data' => [
                'monster' => ['id' => $this->monster->id, 'name' => $this->monster->name, 'str' => 100],
            ],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(5, $response->json('current_foe.pack_size'));
    }

    public function testStatusCurrentFoeMessageForPackGreaterThanOne(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'pack_size' => 10,
            'fight_data' => [
                'monster' => ['id' => $this->monster->id, 'name' => $this->monster->name, 'str' => 100],
            ],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('You are fighting 10 of', $response->json('current_foe.message'));
    }

    public function testStatusCurrentFoeEnemyStrengthBoostFromLog(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'increased_enemy_strength' => 0.5,
            'fight_data' => [
                'monster' => ['id' => $this->monster->id, 'name' => $this->monster->name, 'str' => 100],
            ],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0.5, $response->json('current_foe.enemy_strength_boost'));
    }

    public function testStatusCurrentFoeStatsAvailableFromActiveMonsterModelWhenNoLog(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->json('current_foe.stats_available'));
        $this->assertNotEmpty($response->json('current_foe.stats'));
    }

    public function testStatusCurrentFoeStatsAvailableWhenLogHasMonsterInFightData(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'fight_data' => [
                'monster' => ['id' => $this->monster->id, 'name' => $this->monster->name, 'str' => 300],
            ],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->json('current_foe.stats_available'));
    }

    public function testStatusCurrentFoeBaseStatsFromActiveMonsterModel(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->monster->str, $response->json('current_foe.stats.str'));
        $this->assertSame($this->monster->health_range, $response->json('current_foe.stats.health_range'));
        $this->assertSame($this->monster->xp, $response->json('current_foe.stats.xp'));
        $this->assertSame($this->monster->gold, $response->json('current_foe.stats.gold'));
    }

    public function testStatusCurrentFoeMessageForActiveDelveFallback(): void
    {
        DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'Showing selected monster base stats. Combat-adjusted stats update after each Delve round.',
            $response->json('current_foe.message')
        );
    }

    public function testStatusCurrentFoeStatValuesFromFightData(): void
    {
        $delve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        DelveLog::factory()->create([
            'character_id' => $this->character->id,
            'delve_exploration_id' => $delve->id,
            'fight_data' => [
                'monster' => [
                    'id' => $this->monster->id,
                    'name' => $this->monster->name,
                    'str' => 999,
                    'dur' => 888,
                    'health_range' => '500-1000',
                    'spell_damage' => 75,
                    'max_healing' => 0.25,
                ],
            ],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/delve/' . $this->character->id . '/status');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(999, $response->json('current_foe.stats.str'));
        $this->assertSame(888, $response->json('current_foe.stats.dur'));
        $this->assertSame('500-1000', $response->json('current_foe.stats.health_range'));
        $this->assertSame(75, $response->json('current_foe.stats.max_spell_damage'));
        $this->assertSame(0.25, $response->json('current_foe.stats.healing_percentage'));
    }

    public function testDismissSoftDismissesAllCompletedDelvesAtOnce(): void
    {
        $olderDelve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(2),
            'ended_reason' => 'player_stopped',
            'panel_dismissed_at' => null,
        ]);

        $newerDelve = DelveExploration::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'ended_reason' => 'died',
            'panel_dismissed_at' => null,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/delve/' . $this->character->id . '/dismiss', [
                '_token' => csrf_token(),
            ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->json('active'));
        $this->assertFalse($response->json('completed'));
        $this->assertNotNull($olderDelve->refresh()->panel_dismissed_at);
        $this->assertNotNull($newerDelve->refresh()->panel_dismissed_at);
    }
}
