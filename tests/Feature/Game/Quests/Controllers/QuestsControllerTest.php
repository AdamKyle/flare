<?php

namespace Tests\Feature\Game\Quests\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateQuestsCompleted;

class QuestsControllerTest extends TestCase
{
    use CreateNpc, CreateQuest, CreateQuestsCompleted, RefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->call('GET', '/game/completed-quests/'.$character->user->id);

        $response->assertStatus(302);
    }

    public function test_index_shows_only_the_authenticated_characters_completed_quests(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $npc = $this->createNpc();
        $quest = $this->createQuest(['npc_id' => $npc->id, 'name' => 'Rescue The Miller']);
        $otherQuest = $this->createQuest(['npc_id' => $npc->id, 'name' => 'Bandit Cleanup']);

        $this->createQuestsCompleted(['character_id' => $character->id, 'quest_id' => $quest->id]);
        $this->createQuestsCompleted(['character_id' => $otherCharacter->id, 'quest_id' => $otherQuest->id]);

        $response = $this->actingAs($character->user)->get('/game/completed-quests/'.$character->user->id);

        $response->assertSee('Rescue The Miller');
        $response->assertSee($npc->gameMap->name);
        $response->assertDontSee('Bandit Cleanup');
    }

    public function test_index_excludes_guide_quest_completions(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $npc = $this->createNpc();
        $quest = $this->createQuest(['npc_id' => $npc->id, 'name' => 'Rescue The Miller']);

        $this->createQuestsCompleted(['character_id' => $character->id, 'quest_id' => $quest->id]);
        $this->createQuestsCompleted(['character_id' => $character->id, 'quest_id' => null, 'guide_quest_id' => 1]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/game/completed-quests/'.$character->user->id);

        $response->assertOk();
        $response->assertSee('Rescue The Miller');
    }

    public function test_index_search_filters_by_quest_name(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $npc = $this->createNpc();
        $matchingQuest = $this->createQuest(['npc_id' => $npc->id, 'name' => 'Rescue The Miller']);
        $otherQuest = $this->createQuest(['npc_id' => $npc->id, 'name' => 'Bandit Cleanup']);

        $this->createQuestsCompleted(['character_id' => $character->id, 'quest_id' => $matchingQuest->id]);
        $this->createQuestsCompleted(['character_id' => $character->id, 'quest_id' => $otherQuest->id]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/game/completed-quests/'.$character->user->id, ['search' => 'Rescue']);

        $response->assertOk();
        $response->assertSee('Rescue The Miller');
        $response->assertDontSee('Bandit Cleanup');
    }

    public function test_index_shows_empty_state_when_no_completed_quests(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($character->user)->get('/game/completed-quests/'.$character->user->id);

        $response->assertSee('No completed quests yet.');
    }
}
