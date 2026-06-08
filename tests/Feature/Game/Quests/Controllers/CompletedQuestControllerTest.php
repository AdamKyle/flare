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

    public function testOwnerCanViewCompletedQuest(): void
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

    public function testCharacterCannotViewAnotherCharactersCompletedQuest(): void
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
