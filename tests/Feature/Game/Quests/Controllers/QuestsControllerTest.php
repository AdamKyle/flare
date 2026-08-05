<?php

namespace Tests\Feature\Game\Quests\Controllers;

use App\Flare\Models\QuestsCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class QuestsControllerTest extends TestCase
{
    use CreateNpc, CreateQuest, RefreshDatabase;

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

        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id]);
        QuestsCompleted::factory()->create(['character_id' => $otherCharacter->id, 'quest_id' => $otherQuest->id]);

        $this->actingAs($character->user)
            ->visit('/game/completed-quests/'.$character->user->id)
            ->see('Rescue The Miller')
            ->see($npc->gameMap->name)
            ->dontSee('Bandit Cleanup');
    }

    public function test_index_excludes_guide_quest_completions(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $npc = $this->createNpc();
        $quest = $this->createQuest(['npc_id' => $npc->id, 'name' => 'Rescue The Miller']);

        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $quest->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => null, 'guide_quest_id' => 1]);

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

        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $matchingQuest->id]);
        QuestsCompleted::factory()->create(['character_id' => $character->id, 'quest_id' => $otherQuest->id]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/game/completed-quests/'.$character->user->id, ['search' => 'Rescue']);

        $response->assertOk();
        $response->assertSee('Rescue The Miller');
        $response->assertDontSee('Bandit Cleanup');
    }

    public function test_index_shows_empty_state_when_no_completed_quests(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->actingAs($character->user)
            ->visit('/game/completed-quests/'.$character->user->id)
            ->see('No completed quests yet.');
    }
}
