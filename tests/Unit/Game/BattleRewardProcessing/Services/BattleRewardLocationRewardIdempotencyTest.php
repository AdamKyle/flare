<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleLocationRewardService;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageContext;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class BattleRewardLocationRewardIdempotencyTest extends TestCase
{
    use CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_completed_location_reward_step_cannot_apply_twice(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $battleLocationRewardService = Mockery::mock(BattleLocationRewardService::class);
        $battleLocationRewardService->shouldReceive('setContext')->never();
        $battleLocationRewardService->shouldReceive('planLocationReward')->never();
        $battleLocationRewardService->shouldReceive('applyPlannedLocationReward')->never();
        $this->instance(BattleLocationRewardService::class, $battleLocationRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::SPECIFIC_LOCATION_REWARDS)->firstOrFail()->status);
    }

    public function test_location_reward_plan_is_saved_before_apply(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::SPECIFIC_LOCATION_REWARDS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $battleLocationRewardService = Mockery::mock(BattleLocationRewardService::class);
        $battleLocationRewardService->shouldReceive('setContext')->twice()->andReturnSelf();
        $battleLocationRewardService->shouldReceive('planLocationReward')->once()->andReturn([
            'handler' => 'gold_mines',
            'applies' => true,
            'currencies' => [
                'amounts' => ['gold' => 10],
                'starting' => ['gold' => 0],
                'target' => ['gold' => 10],
            ],
            'items' => [],
            'event' => ['create' => false],
        ]);
        $battleLocationRewardService->shouldReceive('applyPlannedLocationReward')
            ->once()
            ->with(Mockery::any(), Mockery::on(fn (array $plan): bool => $plan['currencies']['amounts']['gold'] === 10))
            ->andReturn(['currencies' => ['gold' => 10], 'item_count' => 0, 'event_created' => false]);
        $this->instance(BattleLocationRewardService::class, $battleLocationRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $step = $request->steps()->where('step_name', BattleRewardStepName::SPECIFIC_LOCATION_REWARDS)->firstOrFail();

        $this->assertSame(10, $step->payload_json['plan']['currencies']['amounts']['gold']);
        $this->assertSame(BattleRewardStepStatus::COMPLETED, $step->status);
    }

    public function test_saved_location_reward_plan_is_reused_on_resume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::SPECIFIC_LOCATION_REWARDS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::SPECIFIC_LOCATION_REWARDS)->update([
            'status' => BattleRewardStepStatus::RESUMABLE,
            'payload_json' => [
                'plan' => [
                    'handler' => 'gold_mines',
                    'applies' => true,
                    'currencies' => [
                        'amounts' => ['gold' => 25],
                        'starting' => ['gold' => 0],
                        'target' => ['gold' => 25],
                    ],
                    'items' => [],
                    'event' => ['create' => false],
                ],
            ],
        ]);
        $battleLocationRewardService = Mockery::mock(BattleLocationRewardService::class);
        $battleLocationRewardService->shouldReceive('setContext')->once()->andReturnSelf();
        $battleLocationRewardService->shouldReceive('planLocationReward')->never();
        $battleLocationRewardService->shouldReceive('applyPlannedLocationReward')
            ->once()
            ->with(Mockery::any(), Mockery::on(fn (array $plan): bool => $plan['currencies']['amounts']['gold'] === 25))
            ->andReturn(['currencies' => ['gold' => 25], 'item_count' => 0, 'event_created' => false]);
        $this->instance(BattleLocationRewardService::class, $battleLocationRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::SPECIFIC_LOCATION_REWARDS)->firstOrFail()->status);
    }

    public function test_applying_same_saved_location_plan_twice_does_not_duplicate_currency_mutation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $plan = [
            'handler' => 'gold_mines',
            'applies' => true,
            'currencies' => [
                'amounts' => ['gold' => 50, 'gold_dust' => 25, 'shards' => 10],
                'starting' => ['gold' => $character->gold, 'gold_dust' => $character->gold_dust, 'shards' => $character->shards],
                'target' => ['gold' => $character->gold + 50, 'gold_dust' => $character->gold_dust + 25, 'shards' => $character->shards + 10],
            ],
            'items' => [],
            'event' => ['create' => false],
        ];

        resolve(BattleLocationRewardService::class)->applyPlannedLocationReward($character, $plan);
        resolve(BattleLocationRewardService::class)->applyPlannedLocationReward($character->refresh(), $plan);

        $this->assertSame($plan['currencies']['target'], [
            'gold' => $character->refresh()->gold,
            'gold_dust' => $character->refresh()->gold_dust,
            'shards' => $character->refresh()->shards,
        ]);
    }

    public function test_location_reward_messages_are_stored_in_outbox(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->user->update([
            'show_gold_per_kill' => true,
            'show_gold_dust_per_kill' => true,
            'show_shards_per_kill' => true,
        ]);
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        DB::table('sessions')->insert([[
            'id' => 'location-message-test',
            'user_id' => $character->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]]);
        resolve(BattleRewardMessageContext::class)->start($request->id, $character->id, $character->user_id);
        resolve(BattleRewardMessageContext::class)->setStep(BattleRewardStepName::SPECIFIC_LOCATION_REWARDS);
        $plan = [
            'handler' => 'gold_mines',
            'applies' => true,
            'currencies' => [
                'amounts' => ['gold' => 50],
                'starting' => ['gold' => $character->gold],
                'target' => ['gold' => $character->gold + 50],
            ],
            'items' => [],
            'event' => ['create' => false],
        ];

        resolve(BattleLocationRewardService::class)->applyPlannedLocationReward($character, $plan);
        resolve(BattleRewardMessageContext::class)->clear();

        $this->assertSame(1, CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->where('step_name', BattleRewardStepName::SPECIFIC_LOCATION_REWARDS)->count());
    }

    public function test_unemitted_location_reward_messages_replay(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::SPECIFIC_LOCATION_REWARDS,
            'emitted_at' => null,
        ]);

        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertNotNull($message->refresh()->emitted_at);
    }

    public function test_emitted_location_reward_messages_do_not_replay(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::SPECIFIC_LOCATION_REWARDS,
            'emitted_at' => now(),
        ]);

        $count = resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertSame(0, $count);
    }
}
