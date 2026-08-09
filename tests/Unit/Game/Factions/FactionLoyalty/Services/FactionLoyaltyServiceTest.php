<?php

namespace Tests\Unit\Game\Factions\FactionLoyalty\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Events\Values\EventType;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateFactionLoyaltyAutomation;
use Tests\Traits\CreateFactionLoyaltyAutomationWarning;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class FactionLoyaltyServiceTest extends TestCase
{
    use CreateEvent, CreateFactionLoyalty, CreateFactionLoyaltyAutomation, CreateFactionLoyaltyAutomationWarning, CreateItem, CreateMonster, CreateNpc, RefreshDatabase;

    private ?Character $character = null;

    private ?FactionLoyaltyService $factionLoyaltyService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();
        $this->factionLoyaltyService = resolve(FactionLoyaltyService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->factionLoyaltyService = null;
    }

    public function test_get_no_faction_loyalty_for_plane()
    {
        $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $result = $this->factionLoyaltyService->getLoyaltyInfoForPlane($this->character);

        $this->assertEquals('You have not pledged to a faction.', $result['message']);
    }

    public function test_has_plane_loyalty()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $character = $this->character->refresh();

        $result = $this->factionLoyaltyService->getLoyaltyInfoForPlane($character);

        $this->assertCount(1, $result['npcs']);
        $this->assertNotNull($result['faction_loyalty']);
        $this->assertEquals($this->character->map->gameMap->name, $result['map_name']);
    }

    public function test_get_loyalty_info_for_plane_includes_latest_unread_warning_notice(): void
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);
        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);
        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);
        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);
        $characterAutomation = CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);
        $automation = $this->createFactionLoyaltyAutomation([
            'character_automation_id' => $characterAutomation->id,
            'character_id' => $this->character->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
        ]);

        $automationLog = $this->createFactionLoyaltyAutomationLog([
            'faction_loyalty_automation_id' => $automation->id,
            'fight_logs' => [
                [
                    'log_entry_id' => 'warning-log-entry',
                    'warning_notice' => [
                        'message' => 'Log warning message.',
                        'read' => false,
                    ],
                ],
            ],
        ]);
        $olderWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
            'faction_loyalty_automation_id' => $automation->id,
            'faction_loyalty_automation_log_id' => $automationLog->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'log_type' => 'fight_logs',
            'log_entry_id' => 'warning-log-entry',
            'type' => 'bounty',
            'message' => 'Older warning message.',
        ]);
        $latestWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
            'faction_loyalty_automation_id' => $automation->id,
            'faction_loyalty_automation_log_id' => $automationLog->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'log_type' => 'fight_logs',
            'log_entry_id' => 'warning-log-entry',
            'type' => 'crafting',
            'message' => 'Latest warning message.',
        ]);

        $result = $this->factionLoyaltyService->getLoyaltyInfoForPlane($this->character->refresh());
        $factionLoyaltyNpc = $result['faction_loyalty']->factionLoyaltyNpcs->first();

        $this->assertEquals([
            'id' => $latestWarning->id,
            'type' => 'crafting',
            'message' => 'Latest warning message.',
        ], $factionLoyaltyNpc->faction_loyalty_warning_notice);
        $this->assertEquals([
            [
                'id' => $latestWarning->id,
                'type' => 'crafting',
                'message' => 'Latest warning message.',
            ],
            [
                'id' => $olderWarning->id,
                'type' => 'bounty',
                'message' => 'Older warning message.',
            ],
        ], $factionLoyaltyNpc->faction_loyalty_warning_notices);
    }

    public function test_dismiss_latest_warning_notice_deletes_latest_warning_notice_and_referenced_log_entry(): void
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);
        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);
        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);
        $characterAutomation = CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);
        $automation = $this->createFactionLoyaltyAutomation([
            'character_automation_id' => $characterAutomation->id,
            'character_id' => $this->character->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
        ]);
        $automationLog = $this->createFactionLoyaltyAutomationLog([
            'faction_loyalty_automation_id' => $automation->id,
            'fight_logs' => [
                [
                    'log_entry_id' => 'older-log-entry',
                    'outcome' => 'older_warning',
                    'monster_id' => 10,
                ],
                [
                    'log_entry_id' => 'latest-log-entry',
                    'outcome' => 'latest_warning',
                    'monster_id' => 20,
                    'warning_notice' => [
                        'message' => 'Log warning message.',
                        'read' => false,
                    ],
                ],
                [
                    'log_entry_id' => 'unrelated-log-entry',
                    'outcome' => 'unrelated_warning',
                    'monster_id' => 30,
                ],
            ],
        ]);
        $olderWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
            'faction_loyalty_automation_id' => $automation->id,
            'faction_loyalty_automation_log_id' => $automationLog->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'log_type' => 'fight_logs',
            'log_entry_id' => 'older-log-entry',
            'type' => 'bounty',
            'message' => 'Older warning message.',
        ]);
        $latestWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
            'faction_loyalty_automation_id' => $automation->id,
            'faction_loyalty_automation_log_id' => $automationLog->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'log_type' => 'fight_logs',
            'log_entry_id' => 'latest-log-entry',
            'type' => 'crafting',
            'message' => 'Latest warning message.',
        ]);

        $this->factionLoyaltyService->dismissLatestWarningNotice($this->character);

        $this->assertNotNull($olderWarning->refresh());
        $this->assertNull($latestWarning->fresh());
        $this->assertEquals([
            [
                'log_entry_id' => 'older-log-entry',
                'outcome' => 'older_warning',
                'monster_id' => 10,
            ],
            [
                'log_entry_id' => 'unrelated-log-entry',
                'outcome' => 'unrelated_warning',
                'monster_id' => 30,
            ],
        ], $automationLog->refresh()->fight_logs);
    }

    public function test_dismiss_latest_warning_notice_does_nothing_when_no_warning_exists(): void
    {
        $this->factionLoyaltyService->dismissLatestWarningNotice($this->character);

        $this->assertEquals(0, FactionLoyaltyAutomationWarning::where('character_id', $this->character->id)->count());
    }

    public function test_get_latest_unread_warning_notice_returns_the_most_recent_warning(): void
    {
        $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
            'message' => 'Older warning.',
        ]);
        $latestWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
            'message' => 'Latest warning.',
        ]);

        $result = $this->factionLoyaltyService->getLatestUnreadWarningNotice($this->character);

        $this->assertSame($latestWarning->id, $result['id']);
        $this->assertSame('Latest warning.', $result['message']);
    }

    public function test_get_latest_unread_warning_notice_returns_null_when_no_warnings_exist(): void
    {
        $result = $this->factionLoyaltyService->getLatestUnreadWarningNotice($this->character);

        $this->assertNull($result);
    }

    public function test_dismiss_warning_notice_by_explicit_warning_id_only_dismisses_that_warning(): void
    {
        $olderWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
        ]);
        $latestWarning = $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $this->character->id,
        ]);

        $this->factionLoyaltyService->dismissLatestWarningNotice($this->character, $olderWarning->id);

        $this->assertNull($olderWarning->fresh());
        $this->assertNotNull($latestWarning->fresh());
    }

    public function test_has_plane_loyalty_for_npc_currently_helping()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $secondNpc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $factionSecondNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $secondNpc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionSecondNpc->id,
            'fame_tasks' => [],
        ]);

        $character = $this->character->refresh();

        $result = $this->factionLoyaltyService->getLoyaltyInfoForPlane($character);

        $this->assertCount(2, $result['npcs']);
        $this->assertEquals($secondNpc->id, $result['faction_loyalty']->factionLoyaltyNpcs->where('currently_helping', true)->first()->npc_id);
        $this->assertEquals($this->character->map->gameMap->name, $result['map_name']);
    }

    public function test_cannot_pledge_with_another_characters_faction()
    {

        $secondCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $result = $this->factionLoyaltyService->pledgeLoyalty($this->character, $secondCharacter->factions->first());

        $this->assertEquals('Nope. Not allowed.', $result['message']);
    }

    public function test_cannot_pledge_to_faction_when_not_maxed()
    {
        $result = $this->factionLoyaltyService->pledgeLoyalty($this->character, $this->character->factions->first());

        $this->assertEquals('You must level the faction to level 5 before being able to assist the fine people of this plane with their tasks.', $result['message']);
    }

    public function test_pledge_loyalty()
    {

        $this->character->factions()->update(['maxed' => true]);

        $this->character = $this->character->refresh();

        $firstNpc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $secondNpc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMultipleMonsters(
            [
                'game_map_id' => $this->character->map->game_map_id,
            ], 10
        );

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'armour',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'ring',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
        ]);

        $result = $this->factionLoyaltyService->pledgeLoyalty($this->character, $this->character->factions->first());

        $character = $this->character->refresh();

        $this->assertEquals('Pledged to: '.$character->map->gameMap->name.'.', $result['message']);

        $character = $character->refresh();

        $this->assertCount(1, $character->factionLoyalties);
        $this->assertTrue($character->factionLoyalties->first()->is_pledged);
        $this->assertCount(2, $character->factionLoyalties->first()->factionLoyaltyNpcs);
        $this->assertCount(6, $character->factionLoyalties->first()->factionLoyaltyNpcs->where('npc_id', '=', $firstNpc->id)->first()->factionLoyaltyNpcTasks->fame_tasks);
        $this->assertCount(6, $character->factionLoyalties->first()->factionLoyaltyNpcs->where('npc_id', '=', $secondNpc->id)->first()->factionLoyaltyNpcTasks->fame_tasks);

        $resultFactions = collect($result['factions']);

        $this->assertNotEmpty($resultFactions->filter(function ($resultFaction) {
            return $resultFaction['is_pledged'];
        }));

        $gameMap = $this->createGameMap([
            'name' => MapName::LABYRINTH->value,
        ]);

        $this->createNpc([
            'game_map_id' => $gameMap->id,
        ]);

        $character->map->update([
            'game_map_id' => $gameMap->id,
        ]);

        $faction = $character->factions()->create([
            'character_id' => $character->id,
            'game_map_id' => $gameMap->id,
            'current_level' => 0,
            'current_points' => 0,
            'points_needed' => 1000,
            'maxed' => true,
            'title' => null,
        ]);

        $this->createMultipleMonsters(
            [
                'game_map_id' => $gameMap->id,
            ], 10
        );

        $character->refresh();

        $result = $this->factionLoyaltyService->pledgeLoyalty($character, $faction);

        $character = $this->character->refresh();

        $this->assertEquals('Pledged to: '.$character->map->gameMap->name.'.', $result['message']);
    }

    public function test_pledge_to_existing_loyalty()
    {
        $this->character->factions()->first()->update(['maxed' => true]);

        $this->character = $this->character->refresh();

        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $secondNpc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $factionSecondNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $secondNpc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionSecondNpc->id,
            'fame_tasks' => [],
        ]);

        $character = $this->character->refresh();

        $result = $this->factionLoyaltyService->pledgeLoyalty($character, $factionLoyalty->faction);

        $character = $this->character->refresh();
        $factionLoyalty = $factionLoyalty->refresh();

        $this->assertEquals('Pledged to: '.$character->map->gameMap->name.'.', $result['message']);
        $this->assertTrue($factionLoyalty->is_pledged);
    }

    public function test_remove_pledge()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $secondNpc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $factionSecondNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $secondNpc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionSecondNpc->id,
            'fame_tasks' => [],
        ]);

        $character = $this->character->refresh();

        $result = $this->factionLoyaltyService->removePledge($character, $factionLoyalty->faction);

        $character = $this->character->refresh();
        $factionLoyalty = $factionLoyalty->refresh();

        $this->assertEquals('No longer pledged to: '.$character->map->gameMap->name.'.', $result['message']);
        $this->assertFalse($factionLoyalty->is_pledged);
    }

    public function test_fail_to_remove_pledged()
    {
        $character = $this->character->refresh();

        $result = $this->factionLoyaltyService->removePledge($character, $character->factions->first());

        $this->assertEquals('Failed to find the faction you are pledged to.', $result['message']);
    }

    public function test_create_new_tasks_for_npc_loyalty_tasks()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'armour',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'ring',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $npcTask = $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $oldTasks = $npcTask->fame_tasks;

        $newNPCtask = $this->factionLoyaltyService->createNewTasksForNpc($npcTask, $this->character);

        $this->assertNotEquals($oldTasks, $newNPCtask->fame_tasks);
    }

    public function test_create_new_tasks_for_npc_loyalty_tasks_uses_dungeons_skill_level_cap()
    {
        $gameMap = $this->createGameMap(['name' => MapName::DUNGEONS->value]);

        $npc = $this->createNpc([
            'game_map_id' => $gameMap->id,
        ]);

        $this->createMultipleMonsters([
            'game_map_id' => $gameMap->id,
        ], 3);

        $this->createItem(['skill_Level_required' => 200, 'skill_level_trivial' => 300, 'crafting_type' => 'weapon']);
        $this->createItem(['skill_Level_required' => 200, 'skill_level_trivial' => 300, 'crafting_type' => 'armour']);
        $this->createItem(['skill_Level_required' => 200, 'skill_level_trivial' => 300, 'crafting_type' => 'ring']);
        $this->createItem(['skill_Level_required' => 200, 'skill_level_trivial' => 300, 'crafting_type' => 'spell']);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $npcTask = $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $newNPCtask = $this->factionLoyaltyService->createNewTasksForNpc($npcTask, $this->character);

        $this->assertNotEmpty($newNPCtask->fame_tasks);
    }

    public function test_create_new_tasks_for_npc_loyalty_tasks_uses_hell_skill_level_cap()
    {
        $gameMap = $this->createGameMap(['name' => MapName::HELL->value]);

        $npc = $this->createNpc([
            'game_map_id' => $gameMap->id,
        ]);

        $this->createMultipleMonsters([
            'game_map_id' => $gameMap->id,
        ], 3);

        $this->createItem(['skill_Level_required' => 260, 'skill_level_trivial' => 350, 'crafting_type' => 'weapon']);
        $this->createItem(['skill_Level_required' => 260, 'skill_level_trivial' => 350, 'crafting_type' => 'armour']);
        $this->createItem(['skill_Level_required' => 260, 'skill_level_trivial' => 350, 'crafting_type' => 'ring']);
        $this->createItem(['skill_Level_required' => 260, 'skill_level_trivial' => 350, 'crafting_type' => 'spell']);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $npcTask = $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $newNPCtask = $this->factionLoyaltyService->createNewTasksForNpc($npcTask, $this->character);

        $this->assertNotEmpty($newNPCtask->fame_tasks);
    }

    public function test_create_new_tasks_for_npc_loyalty_tasks_when_on_event_plane_with_out_purgatory_item()
    {

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $this->character->map()->update([
            'game_map_id' => $this->createGameMap([
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
                'name' => MapName::DELUSIONAL_MEMORIES->value,
            ])->id,
        ]);

        $surfaceGameMap = GameMap::where('name', MapName::SURFACE->value)->first();

        $this->character = $this->character->refresh();

        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'armour',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'ring',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $npcTask = $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $oldTasks = $npcTask->fame_tasks;

        $newNPCtask = $this->factionLoyaltyService->createNewTasksForNpc($npcTask, $this->character);

        $this->assertNotEquals($oldTasks, $newNPCtask->fame_tasks);

        $bountyTasks = collect($newNPCtask->fame_tasks)->where('type', 'bounty');

        $this->assertTrue($bountyTasks->every(function (array $task): bool {
            return Monster::find($task['monster_id'])->gameMap->name === MapName::SURFACE->value;
        }));
    }

    public function test_create_new_tasks_for_npc_loyalty_tasks_when_on_event_plane_with_purgatory_item()
    {

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $this->character->map()->update([
            'game_map_id' => $this->createGameMap([
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
                'name' => MapName::DELUSIONAL_MEMORIES->value,
            ])->id,
        ]);

        $this->character = $this->character->refresh();

        $surfaceGameMap = GameMap::where('name', MapName::SURFACE->value)->first();

        $item = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::PURGATORY->value,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
        ]);

        $this->character = $this->character->refresh();

        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createMonster([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'armour',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'ring',
        ]);

        $this->createItem([
            'skill_Level_required' => 10,
            'skill_level_trivial' => 100,
            'crafting_type' => 'spell',
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $npcTask = $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $oldTasks = $npcTask->fame_tasks;

        $newNPCtask = $this->factionLoyaltyService->createNewTasksForNpc($npcTask, $this->character);

        $this->assertNotEquals($oldTasks, $newNPCtask->fame_tasks);

        $bountyTasks = collect($newNPCtask->fame_tasks)->where('type', 'bounty');

        $this->assertTrue($bountyTasks->every(function (array $task): bool {
            return Monster::find($task['monster_id'])->gameMap->name === MapName::DELUSIONAL_MEMORIES->value;
        }));
    }

    public function test_fail_to_assist_npc_that_does_not_belong_to_character()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $character->factions->first()->id,
            'character_id' => $character->id,
        ]);

        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $result = $this->factionLoyaltyService->assistNpc($this->character, $factionNpc);

        $this->assertEquals('Nope. Not allowed.', $result['message']);
    }

    public function test_assist_npc()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $character = $this->character->refresh();

        $result = $this->factionLoyaltyService->assistNpc($character, $factionNpc);

        $character = $character->refresh();

        $this->assertTrue(
            $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->currently_helping
        );

        $this->assertEquals('You are now assisting '.$factionNpc->npc->real_name.' with their tasks!', $result['message']);
    }

    public function test_fail_to_stop_assisting_npc_character_does_not_own()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $character->factions->first()->id,
            'character_id' => $character->id,
        ]);

        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $result = $this->factionLoyaltyService->stopAssistingNpc($this->character, $factionNpc);

        $this->assertEquals('Nope. Not allowed.', $result['message']);
    }

    public function test_stop_assisting_npc()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $character = $this->character->refresh();

        $result = $this->factionLoyaltyService->stopAssistingNpc($character, $factionNpc);

        $this->assertFalse(
            $character->factionLoyalties->first()->factionLoyaltyNpcs->first()->currently_helping
        );

        $this->assertEquals('You stopped assisting '.$factionNpc->npc->real_name.' with their tasks. They are sad but understand.', $result['message']);
    }
}
