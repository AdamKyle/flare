<?php

namespace Tests\Feature\Admin\Items;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ItemQuestItemFactualNavigationIdsTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateMonster, CreateNpc, CreateQuest, CreateRole, CreateUser, RefreshDatabase;

    public function test_quest_item_factual_payload_carries_every_relationship_id_navigation_needs(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $dropMap = $this->createGameMap(['name' => 'Drop Map']);
        $requiredMap = $this->createGameMap(['name' => 'Required Map']);
        $rewardMap = $this->createGameMap(['name' => 'Reward Map']);
        $npc = $this->createNpc(['game_map_id' => $dropMap->id, 'real_name' => 'Quest Giver']);

        $questItem = $this->createItem(['type' => 'quest', 'usable' => false]);

        $dropLocation = $this->createLocation(['name' => 'Drop Location', 'game_map_id' => $dropMap->id]);
        $questItem->update(['drop_location_id' => $dropLocation->id]);

        $requiredLocation = $this->createLocation(['name' => 'Required Location', 'game_map_id' => $requiredMap->id, 'required_quest_item_id' => $questItem->id]);
        $rewardLocation = $this->createLocation(['name' => 'Reward Location', 'game_map_id' => $rewardMap->id, 'quest_reward_item_id' => $questItem->id]);

        $requiredQuest = $this->createQuest(['name' => 'Required By Quest', 'npc_id' => $npc->id, 'item_id' => $questItem->id]);
        $rewardQuest = $this->createQuest(['name' => 'Reward Quest', 'npc_id' => $npc->id, 'reward_item' => $questItem->id]);

        $monster = $this->createMonster(['name' => 'Dropping Monster', 'game_map_id' => $dropMap->id, 'quest_item_id' => $questItem->id]);

        $response = $this->actingAs($admin)->call(
            'GET',
            "/api/admin/items/{$questItem->id}",
            [], [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertJsonPath('presentation.drop_location.id', $dropLocation->id);
        $response->assertJsonPath('presentation.drop_location.game_map.id', $dropMap->id);

        $response->assertJsonPath('presentation.required_locations.0.id', $requiredLocation->id);
        $response->assertJsonPath('presentation.required_locations.0.game_map.id', $requiredMap->id);

        $response->assertJsonPath('presentation.reward_locations.0.id', $rewardLocation->id);
        $response->assertJsonPath('presentation.reward_locations.0.game_map.id', $rewardMap->id);

        $response->assertJsonPath('presentation.required_quests.0.id', $requiredQuest->id);
        $response->assertJsonPath('presentation.required_quests.0.npc.id', $npc->id);
        $response->assertJsonPath('presentation.required_quests.0.game_map.id', $dropMap->id);

        $response->assertJsonPath('presentation.reward_quests.0.id', $rewardQuest->id);
        $response->assertJsonPath('presentation.reward_quests.0.npc.id', $npc->id);

        $response->assertJsonPath('presentation.required_monsters.0.id', $monster->id);
        $response->assertJsonPath('presentation.required_monsters.0.game_map.id', $dropMap->id);
    }
}
