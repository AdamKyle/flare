<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardProgressionUpdated;
use App\Game\BattleRewardProcessing\Services\BattleRewardPresentationService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;

class BattleRewardPresentationServiceTest extends TestCase
{
    use CreateCharacterBattleReward, RefreshDatabase;

    public function test_present_publishes_the_final_player_update_and_completes_both_presentation_steps(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);

        resolve(BattleRewardPresentationService::class)->present($request);

        $this->assertSame(
            [BattleRewardStepStatus::COMPLETED, BattleRewardStepStatus::COMPLETED],
            $request->steps()->orderBy('id')->pluck('status')->all(),
        );
        Event::assertDispatched(UpdateBaseCharacterInformation::class);
    }

    public function test_present_broadcasts_each_level_snapshot_immediately_before_its_level_up_message(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::COMPLETED,
            'checkpoint_json' => [
                'remaining_xp' => 0,
                'progression' => [
                    ['level' => 2, 'xp' => 150, 'xp_next' => 100],
                    ['level' => 3, 'xp' => 50, 'xp_next' => 100],
                ],
            ],
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'message' => 'You are now level: 2!',
        ]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'message' => 'You are now level: 3!',
        ]);
        $timeline = [];
        Event::listen(BattleRewardProgressionUpdated::class, function (BattleRewardProgressionUpdated $event) use (&$timeline): void {
            $timeline[] = 'progression: '.$event->level.' '.$event->xp.'/'.$event->xpNext;
        });
        Event::listen(ServerMessageEvent::class, function (ServerMessageEvent $event) use (&$timeline): void {
            $timeline[] = 'message: '.$event->message;
        });

        resolve(BattleRewardPresentationService::class)->present($request);

        $this->assertSame([
            'progression: 2 150/100',
            'message: You are now level: 2!',
            'progression: 3 50/100',
            'message: You are now level: 3!',
        ], $timeline);
    }

    public function test_present_does_not_broadcast_progression_for_xp_summary_or_non_xp_step_messages(): void
    {
        Event::fake([BattleRewardProgressionUpdated::class, ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::COMPLETED,
            'checkpoint_json' => [
                'remaining_xp' => 0,
                'progression' => [['level' => 2, 'xp' => 0, 'xp_next' => 100]],
            ],
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'message' => 'You slaughtered: 3 creatures and gained a total of: 100 XP.',
        ]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::FACTION_LOYALTY_XP,
            'message' => 'You are now level: 2!',
        ]);

        resolve(BattleRewardPresentationService::class)->present($request);

        Event::assertDispatchedTimes(ServerMessageEvent::class, 2);
        Event::assertNotDispatched(BattleRewardProgressionUpdated::class);
    }

    public function test_present_retry_skips_already_emitted_messages_and_their_progression_snapshots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::COMPLETED,
            'checkpoint_json' => [
                'remaining_xp' => 0,
                'progression' => [
                    ['level' => 2, 'xp' => 150, 'xp_next' => 100],
                    ['level' => 3, 'xp' => 50, 'xp_next' => 100],
                ],
            ],
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);
        $messageOutboxStep = $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
            'status' => BattleRewardStepStatus::FAILED,
        ]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'message' => 'You are now level: 2!',
            'emitted_at' => now()->subSeconds(5),
        ]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'message' => 'You are now level: 3!',
        ]);
        $timeline = [];
        Event::listen(BattleRewardProgressionUpdated::class, function (BattleRewardProgressionUpdated $event) use (&$timeline): void {
            $timeline[] = 'progression: '.$event->level;
        });
        Event::listen(ServerMessageEvent::class, function (ServerMessageEvent $event) use (&$timeline): void {
            $timeline[] = 'message: '.$event->message;
        });

        resolve(BattleRewardPresentationService::class)->present($request);

        $this->assertSame(['progression: 3', 'message: You are now level: 3!'], $timeline);
        $this->assertSame(BattleRewardStepStatus::COMPLETED, $messageOutboxStep->refresh()->status);
    }

    public function test_present_fails_the_message_outbox_step_and_rethrows_when_a_message_cannot_be_emitted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);
        $messageOutboxStep = $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'message' => 'You gained: 10 XP and now you have: 10',
        ]);
        Event::listen(ServerMessageEvent::class, function (): void {
            throw new RuntimeException('broadcast failed');
        });

        try {
            resolve(BattleRewardPresentationService::class)->present($request);
        } catch (RuntimeException $exception) {
            $this->assertSame('broadcast failed', $exception->getMessage());
        }

        $this->assertSame(BattleRewardStepStatus::FAILED, $messageOutboxStep->refresh()->status);
        $this->assertNull($message->refresh()->emitted_at);
    }
}
