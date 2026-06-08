<?php

namespace Tests\Unit\Game\Quests\Jobs;

use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use App\Game\Quests\Handlers\NpcQuestsHandler;
use App\Game\Quests\Jobs\HandInQuest;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class HandInQuestTest extends TestCase
{
    use CreateNpc, CreateQuest, RefreshDatabase;

    public function test_successful_reward_handling_creates_quest_log_and_fires_completed_message(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);
        $rewardHandler = resolve(NpcQuestRewardHandler::class);

        $npcQuestsHandler = Mockery::mock(NpcQuestsHandler::class);
        $npcQuestsHandler->shouldReceive('handleNpcQuest')
            ->once()
            ->with($character, $quest);
        $npcQuestsHandler->shouldReceive('questRewardHandler')
            ->once()
            ->andReturn($rewardHandler);

        (new HandInQuest($character, $quest))->handle($npcQuestsHandler);

        $this->assertEquals(1, $character->fresh()->questsCompleted()->where('quest_id', $quest->id)->count());
        Event::assertDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($character, $quest, $npc): bool {
            return $event->message === $character->name . ' Has completed a quest (' . $quest->name . ') for: ' . $npc->real_name . ' and been rewarded with a godly gift!';
        });
    }

    public function test_failed_reward_handling_does_not_create_completed_quest_log(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);

        $npcQuestsHandler = Mockery::mock(NpcQuestsHandler::class);
        $npcQuestsHandler->shouldReceive('handleNpcQuest')
            ->once()
            ->with($character, $quest)
            ->andThrow(new Exception('Reward failed.'));

        try {
            (new HandInQuest($character, $quest))->handle($npcQuestsHandler);
        } catch (Exception) {
        }

        $this->assertEquals(0, $character->fresh()->questsCompleted()->where('quest_id', $quest->id)->count());
    }

    public function test_failed_reward_handling_does_not_fire_completed_message(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);

        $npcQuestsHandler = Mockery::mock(NpcQuestsHandler::class);
        $npcQuestsHandler->shouldReceive('handleNpcQuest')
            ->once()
            ->with($character, $quest)
            ->andThrow(new Exception('Reward failed.'));

        try {
            (new HandInQuest($character, $quest))->handle($npcQuestsHandler);
        } catch (Exception) {
        }

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }

    public function test_failed_reward_handling_logs_and_rethrows_exception(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);
        $exception = new Exception('Reward failed.');

        Log::shouldReceive('error')
            ->once()
            ->with('Reward failed.');

        $npcQuestsHandler = Mockery::mock(NpcQuestsHandler::class);
        $npcQuestsHandler->shouldReceive('handleNpcQuest')
            ->once()
            ->with($character, $quest)
            ->andThrow($exception);

        $this->expectExceptionObject($exception);

        (new HandInQuest($character, $quest))->handle($npcQuestsHandler);
    }
}
