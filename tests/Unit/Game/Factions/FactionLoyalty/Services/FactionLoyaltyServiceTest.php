<?php

namespace Tests\Unit\Game\Factions\FactionLoyalty\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Values\EventType;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class FactionLoyaltyServiceTest extends TestCase
{
    use CreateEvent, CreateFactionLoyalty, CreateItem, CreateMonster, CreateNpc, RefreshDatabase;

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

        foreach (MapNameValue::$values as $value) {

            if ($value === MapNameValue::SURFACE) {
                continue;
            }

            $gameMap = $this->createGameMap([
                'name' => $value,
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

    public function test_create_new_tasks_for_npc_loyalty_tasks_when_on_event_plane_with_out_purgatory_item()
    {

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $this->character->map()->update([
            'game_map_id' => $this->createGameMap([
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
                'name' => MapNameValue::DELUSIONAL_MEMORIES,
            ])->id,
        ]);

        $surfaceGameMap = GameMap::where('name', MapNameValue::SURFACE)->first();

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

        foreach ($newNPCtask->fame_tasks as $task) {
            if ($task['type'] === 'bounty') {
                $monster = Monster::find($task['monster_id']);

                $this->assertEquals(MapNameValue::SURFACE, $monster->gameMap->name);
            }
        }
    }

    public function test_create_new_tasks_for_npc_loyalty_tasks_when_on_event_plane_with_purgatory_item()
    {

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $this->character->map()->update([
            'game_map_id' => $this->createGameMap([
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
                'name' => MapNameValue::DELUSIONAL_MEMORIES,
            ])->id,
        ]);

        $this->character = $this->character->refresh();

        $surfaceGameMap = GameMap::where('name', MapNameValue::SURFACE)->first();

        $item = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::PURGATORY,
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

        foreach ($newNPCtask->fame_tasks as $task) {
            if ($task['type'] === 'bounty') {
                $monster = Monster::find($task['monster_id']);

                $this->assertEquals(MapNameValue::DELUSIONAL_MEMORIES, $monster->gameMap->name);
            }
        }
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
