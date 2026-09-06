<?php

namespace Tests\Unit\Game\Automation\Delve\Services;

use App\Game\Automation\Delve\Services\DelveStatusService;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateDelveAutomation;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class DelveStatusServiceTest extends TestCase
{
    use CreateDelveAutomation, CreateDelveExploration, CreateItem, CreateLocation, CreateMonster, CreateNpc, CreateQuest, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?DelveStatusService $delveStatusService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->delveStatusService = resolve(DelveStatusService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->delveStatusService = null;
    }

    public function test_quest_item_detail_returns_transformed_item(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $result = $this->delveStatusService->questItemDetail($item);

        $this->assertSame($item->id, $result['id']);
    }

    public function test_status_for_character_returns_inactive_when_no_delve_exists(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertSame(['active' => false, 'completed' => false], $result);
    }

    public function test_status_for_character_returns_active_status_for_active_delve(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertTrue($result['active']);
        $this->assertFalse($result['completed']);
        $this->assertSame($monster->name, $result['monster_name']);
        $this->assertTrue($result['current_foe']['stats_available']);
        $this->assertSame('active_delve', $result['current_foe']['source']);
    }

    public function test_status_for_character_returns_completed_status_for_undismissed_completed_delve(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $delve = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(3),
            'completed_at' => now(),
            'ended_reason' => 'died',
            'panel_dismissed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertFalse($result['active']);
        $this->assertTrue($result['completed']);
        $this->assertSame($delve->id, $result['id']);
        $this->assertSame('died', $result['reason']);
    }

    public function test_status_for_character_ignores_dismissed_completed_delve(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(3),
            'completed_at' => now(),
            'ended_reason' => 'died',
            'panel_dismissed_at' => now(),
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertSame(['active' => false, 'completed' => false], $result);
    }

    public function test_status_for_character_includes_quest_item_drop_countdown_when_cave_location_exists(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createLocation([
            'type' => LocationType::CAVE_OF_SHADOWS->value,
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'hours_to_drop' => 4,
        ]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(1),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertSame(4, $result['quest_item_drop_hours_required']);
        $this->assertFalse($result['quest_item_drop_available']);
    }

    public function test_status_for_character_marks_quest_item_drop_available_when_hours_to_drop_is_zero(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createLocation([
            'type' => LocationType::CAVE_OF_SHADOWS->value,
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'hours_to_drop' => 0,
        ]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertTrue($result['quest_item_drop_available']);
        $this->assertSame(0, $result['quest_item_drop_seconds_remaining']);
    }

    public function test_status_for_character_marks_quest_item_drop_available_when_enough_time_has_elapsed(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createLocation([
            'type' => LocationType::CAVE_OF_SHADOWS->value,
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'hours_to_drop' => 1,
        ]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(10),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertTrue($result['quest_item_drop_available']);
        $this->assertSame(0, $result['quest_item_drop_seconds_remaining']);
    }

    public function test_status_for_character_returns_empty_quest_items_when_no_cave_location_exists(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertSame([], $result['quest_items']);
        $this->assertNull($result['quest_item_drop_hours_required']);
    }

    public function test_status_for_character_reports_quest_items_the_character_already_owns(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $location = $this->createLocation([
            'type' => LocationType::CAVE_OF_SHADOWS->value,
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'hours_to_drop' => 1,
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertCount(1, $result['quest_items']);
        $this->assertTrue($result['quest_items'][0]['have']);
    }

    public function test_status_for_character_uses_latest_log_fight_data_for_current_foe_when_available(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $delve = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $this->createDelveAutomationLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'pack_size' => 3,
            'outcome' => 'survived',
            'fight_data' => [
                'monster' => [
                    'id' => $monster->id,
                    'name' => 'Log Monster',
                    'str' => 5,
                ],
            ],
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertSame('latest_log', $result['current_foe']['source']);
        $this->assertSame('Log Monster', $result['current_foe']['name']);
        $this->assertStringContainsString('You are fighting 3 of Log Monster.', $result['current_foe']['message']);
    }

    public function test_status_for_character_reports_waiting_current_foe_when_no_monster_or_log_data_exists(): void
    {
        $character = $this->character->getCharacter();

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => 999999,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertSame('waiting', $result['current_foe']['source']);
        $this->assertFalse($result['current_foe']['stats_available']);
    }

    public function test_dismiss_for_character_marks_completed_delve_as_dismissed(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $delve = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(1),
            'completed_at' => now(),
            'panel_dismissed_at' => null,
        ]);

        $this->delveStatusService->dismissForCharacter($character);

        $this->assertNotNull($delve->fresh()->panel_dismissed_at);
    }

    public function test_status_for_character_marks_quest_item_as_previously_had_when_a_completed_quest_required_it(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $location = $this->createLocation([
            'type' => LocationType::CAVE_OF_SHADOWS->value,
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'hours_to_drop' => 1,
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
        ]);

        $npc = $this->createNpc(['game_map_id' => $character->map->game_map_id]);

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $questItem->id,
        ]);

        $this->createCompletedQuest([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character);

        $this->assertCount(1, $result['quest_items']);
        $this->assertTrue($result['quest_items'][0]['had']);
    }

    public function test_status_for_character_reports_quest_items_as_not_owned_when_character_has_no_inventory(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $location = $this->createLocation([
            'type' => LocationType::CAVE_OF_SHADOWS->value,
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'hours_to_drop' => 1,
        ]);

        $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
        ]);

        $character->inventory()->delete();

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->delveStatusService->statusForCharacter($character->refresh());

        $this->assertCount(1, $result['quest_items']);
        $this->assertFalse($result['quest_items'][0]['have']);
    }
}
