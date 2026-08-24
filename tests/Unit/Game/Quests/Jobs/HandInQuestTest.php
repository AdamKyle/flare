<?php

namespace Tests\Unit\Game\Quests\Jobs;

use App\Flare\Models\Character;
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
            ->with(
                Mockery::on(fn (Character $queuedCharacter): bool => $queuedCharacter->is($character)),
                Mockery::on(fn ($queuedQuest): bool => $queuedQuest->is($quest)),
            );
        $npcQuestsHandler->shouldReceive('questRewardHandler')
            ->twice()
            ->andReturn($rewardHandler);

        $this->app->instance(NpcQuestsHandler::class, $npcQuestsHandler);

        HandInQuest::dispatch($character, $quest);

        $this->assertEquals(1, $character->fresh()->questsCompleted()->where('quest_id', $quest->id)->count());
        Event::assertDispatched(GlobalMessageEvent::class, function (GlobalMessageEvent $event) use ($character, $quest, $npc): bool {
            return $event->message === $character->name.' Has completed a quest ('.$quest->name.') for: '.$npc->real_name.' and been rewarded with a godly gift!';
        });
    }

    public function test_failed_reward_handling_rethrows_exception(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);

        $npcQuestsHandler = Mockery::mock(NpcQuestsHandler::class);
        $npcQuestsHandler->shouldReceive('handleNpcQuest')
            ->once()
            ->with(
                Mockery::on(fn (Character $queuedCharacter): bool => $queuedCharacter->is($character)),
                Mockery::on(fn ($queuedQuest): bool => $queuedQuest->is($quest)),
            )
            ->andThrow(new Exception('Reward failed.'));

        $this->app->instance(NpcQuestsHandler::class, $npcQuestsHandler);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Reward failed.');

        HandInQuest::dispatch($character, $quest);
    }

    public function test_failed_reward_handling_logs_and_rethrows_exception(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);
        $exception = new Exception('Reward failed.');

        $npcQuestsHandler = Mockery::mock(NpcQuestsHandler::class);
        $npcQuestsHandler->shouldReceive('handleNpcQuest')
            ->once()
            ->with(
                Mockery::on(fn (Character $queuedCharacter): bool => $queuedCharacter->is($character)),
                Mockery::on(fn ($queuedQuest): bool => $queuedQuest->is($quest)),
            )
            ->andThrow($exception);

        $this->app->instance(NpcQuestsHandler::class, $npcQuestsHandler);

        Log::shouldReceive('error')->once()->with('Reward failed.');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Reward failed.');

        HandInQuest::dispatch($character, $quest);
    }

    public function test_quest_completion_log_is_written_before_xp_reward_processing(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);
        $rewardHandler = Mockery::mock(NpcQuestRewardHandler::class);
        $rewardHandler->shouldReceive('createquestQuestLog')
            ->once()
            ->ordered()
            ->andReturnUsing(function (Character $rewardedCharacter, $rewardedQuest): void {
                $rewardedCharacter->questsCompleted()->create([
                    'character_id' => $rewardedCharacter->id,
                    'quest_id' => $rewardedQuest->id,
                ]);
            });
        $rewardHandler->shouldReceive('processXpReward')
            ->once()
            ->ordered()
            ->andReturnUsing(function ($rewardedQuest, Character $rewardedCharacter) use ($quest): void {
                $this->assertEquals(1, $rewardedCharacter->questsCompleted()->where('quest_id', $quest->id)->count());
            });

        $npcQuestsHandler = Mockery::mock(NpcQuestsHandler::class);
        $npcQuestsHandler->shouldReceive('handleNpcQuest')->once();
        $npcQuestsHandler->shouldReceive('questRewardHandler')->twice()->andReturn($rewardHandler);

        $this->app->instance(NpcQuestsHandler::class, $npcQuestsHandler);

        HandInQuest::dispatch($character, $quest);
    }

    public function test_global_quest_completion_event_happens_after_quest_completion_log_exists(): void
    {
        Event::fake();

        $npc = $this->createNpc();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest(['npc_id' => $npc->id]);
        $rewardHandler = Mockery::mock(NpcQuestRewardHandler::class);
        $rewardHandler->shouldReceive('createquestQuestLog')
            ->once()
            ->andReturnUsing(function (Character $rewardedCharacter, $rewardedQuest): void {
                $rewardedCharacter->questsCompleted()->create([
                    'character_id' => $rewardedCharacter->id,
                    'quest_id' => $rewardedQuest->id,
                ]);
            });
        $rewardHandler->shouldReceive('processXpReward')
            ->once()
            ->andReturnUsing(function () use ($character, $quest): void {
                Event::assertDispatched(GlobalMessageEvent::class);
                $this->assertEquals(1, $character->questsCompleted()->where('quest_id', $quest->id)->count());
            });

        $npcQuestsHandler = Mockery::mock(NpcQuestsHandler::class);
        $npcQuestsHandler->shouldReceive('handleNpcQuest')->once();
        $npcQuestsHandler->shouldReceive('questRewardHandler')->twice()->andReturn($rewardHandler);

        $this->app->instance(NpcQuestsHandler::class, $npcQuestsHandler);

        HandInQuest::dispatch($character, $quest);
    }
}
