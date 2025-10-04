<?php

namespace Tests\Feature\Game\Factions\FactionLoyalty\Controllers\Api;

use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class FactionLoyaltyControllerTest extends TestCase
{
    use CreateFactionLoyalty, CreateItem, CreateMonster, CreateNpc, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_faction_loyalties()
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

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/faction-loyalty/'.$this->character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['npcs']);
        $this->assertNotEmpty($jsonData['faction_loyalty']);
        $this->assertEquals($jsonData['map_name'], $character->map->gameMap->name);
    }

    public function test_pledge_loyalty_controller_action()
    {
        $this->character->factions()->update(['maxed' => true]);

        $this->character = $this->character->refresh();

        $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $this->createNpc([
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

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty/pledge/'.$this->character->id.'/'.$this->character->factions->first()->id, [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals('Pledged to: '.$this->character->map->gameMap->name.'.', $response['message']);
    }

    public function test_remove_pledged_loyalty()
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

        $this->character = $this->character->refresh();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty/remove-pledge/'.$this->character->id.'/'.$this->character->factions->first()->id, [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals('No longer pledged to: '.$this->character->map->gameMap->name.'.', $response['message']);
    }

    public function test_assist_npc_with_tasks()
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

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $this->character = $this->character->refresh();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty/assist/'.$this->character->id.'/'.$factionNpc->id, [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals('You are now assisting '.$factionNpc->npc->real_name.' with their tasks!', $response['message']);
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

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $this->character = $this->character->refresh();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/faction-loyalty/stop-assisting/'.$this->character->id.'/'.$factionNpc->id, [
                '_token' => csrf_token(),
            ]);

        $this->assertEquals('You stopped assisting '.$factionNpc->npc->real_name.' with their tasks. They are sad but understand.', $response['message']);
    }
}
