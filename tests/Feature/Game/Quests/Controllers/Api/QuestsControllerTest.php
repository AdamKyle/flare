<?php

namespace Tests\Feature\Game\Quests\Controllers\Api;

use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class QuestsControllerTest extends TestCase
{
    use CreateNpc, CreateQuest, RefreshDatabase;

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
