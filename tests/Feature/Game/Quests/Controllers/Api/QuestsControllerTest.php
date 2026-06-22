<?php

namespace Tests\Feature\Game\Quests\Controllers\Api;

use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class QuestsControllerTest extends TestCase
{
    use CreateItem, CreateLocation, CreateNpc, CreateQuest, RefreshDatabase;

    public function testRegularQuestListCanBeFetchedDuringExploration(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/quests/' . $character->id);

        $response->assertOk();
        $this->assertArrayHasKey('quests', $response->json());
    }

    public function testRegularQuestInfoCanBeFetchedDuringExploration(): void
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
            ->call('GET', '/api/quest/' . $quest->id . '/' . $character->id);

        $response->assertOk();
        $response->assertJsonPath('id', $quest->id);
    }

    public function testRegularQuestHandInIsBlockedDuringExploration(): void
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
            ->call('POST', '/api/quest/' . $quest->id . '/hand-in-quest/' . $character->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot do that while Exploration automation is running. Cancel it first.',
        ]);
    }

    public function testRegularQuestHandInIsBlockedDuringDelve(): void
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
            ->call('POST', '/api/quest/' . $quest->id . '/hand-in-quest/' . $character->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot do that while Delve automation is running. Cancel it first.',
        ]);
    }

    public function testQuestItemDropLocationPayloadIncludesDelveFieldsForDelveLocation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $npc = $this->createNpc();
        $location = $this->createLocation([
            'hours_to_drop' => 2,
            'delve_enemy_strength_increase' => 0.05,
        ]);
        $item = $this->createItem(['type' => 'quest', 'drop_location_id' => $location->id]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $item->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/quest/' . $quest->id . '/' . $character->id);

        $response->assertOk();
        $this->assertEquals(2, $response->json('item.drop_location.hours_to_drop'));
        $this->assertEquals(0.05, $response->json('item.drop_location.delve_enemy_strength_increase'));
    }

    public function testQuestItemDropLocationPayloadHasNoDelveFieldsForNormalSpecialLocation(): void
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
            ->call('GET', '/api/quest/' . $quest->id . '/' . $character->id);

        $response->assertOk();
        $hoursToDropValue = $response->json('item.drop_location.hours_to_drop');
        $this->assertTrue(
            $hoursToDropValue === null || $hoursToDropValue === 0,
            'Normal location should not have hours_to_drop > 0'
        );
    }

    public function testRegularQuestHandInIsBlockedDuringFactionLoyalty(): void
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
            ->call('POST', '/api/quest/' . $quest->id . '/hand-in-quest/' . $character->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot do that while Faction Loyalty automation is running. Cancel it first.',
        ]);
    }
}
