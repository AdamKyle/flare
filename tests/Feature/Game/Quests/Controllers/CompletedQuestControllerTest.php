<?php

namespace Tests\Feature\Game\Quests\Controllers;

use App\Flare\Models\QuestsCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class CompletedQuestControllerTest extends TestCase
{
    use CreateItem, CreateNpc, CreateQuest, RefreshDatabase;

    public function test_owner_can_view_completed_quest(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $completedQuest = $this->completedQuestFor($character->id);

        $response = $this->actingAs($character->user)->call('GET', route('completed.quest', [
            'character' => $character->id,
            'questsCompleted' => $completedQuest->id,
        ]));

        $response->assertOk();
        $response->assertSee($completedQuest->quest->name);
    }

    public function test_character_cannot_view_another_characters_completed_quest(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $completedQuest = $this->completedQuestFor($otherCharacter->id);

        $response = $this->actingAs($character->user)->call('GET', route('completed.quest', [
            'character' => $character->id,
            'questsCompleted' => $completedQuest->id,
        ]));

        $response->assertNotFound();
    }

    private function completedQuestFor(int $characterId): QuestsCompleted
    {
        $quest = $this->createQuest([
            'npc_id' => $this->createNpc()->id,
            'item_id' => $this->createItem()->id,
            'unlocks_skill' => false,
        ]);

        return QuestsCompleted::create([
            'character_id' => $characterId,
            'quest_id' => $quest->id,
        ]);
    }
}
