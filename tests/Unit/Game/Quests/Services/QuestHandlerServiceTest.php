<?php

namespace Tests\Unit\Game\Quests\Services;

use App\Game\Quests\Services\QuestHandlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class QuestHandlerServiceTest extends TestCase
{
    use CreateNpc, CreateQuest, RefreshDatabase;

    public function testHandInQuestResponseIncludesCompletedQuestInCompletedQuestsList(): void
    {
        Event::fake();

        $npc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_skill' => false,
            'gold_cost' => 100,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gold' => 200]);

        $result = resolve(QuestHandlerService::class)->handInQuest($character->refresh(), $quest);

        $this->assertEquals(200, $result['status']);
        $this->assertTrue(in_array($quest->id, $result['completed_quests']->toArray()));
    }

    public function testHandInQuestCompletesQuestSynchronouslyWithoutRequiringRefresh(): void
    {
        Event::fake();

        $npc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'unlocks_skill' => false,
            'gold_cost' => 100,
            'gold_dust_cost' => 0,
            'shard_cost' => 0,
            'reward_gold' => null,
            'reward_gold_dust' => null,
            'reward_shards' => null,
            'reward_xp' => null,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->update(['gold' => 200]);

        resolve(QuestHandlerService::class)->handInQuest($character->refresh(), $quest);

        $this->assertEquals(1, $character->fresh()->questsCompleted()->where('quest_id', $quest->id)->count());
    }
}
