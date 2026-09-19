<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PDOException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;

class BattleRewardProcessingQueueManagerTest extends TestCase
{
    use CreateCharacterBattleReward, RefreshDatabase;

    public function test_ensure_processor_running_returns_false_immediately_when_the_live_lock_is_held(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardQueueState([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $lock = Cache::lock('character-reward-queue:'.$character->id, 1800);
        $lock->get();

        $result = resolve(BattleRewardProcessingQueueManager::class)->ensureProcessorRunning($character->id);

        $lock->release();

        $this->assertFalse($result);
        Queue::assertNothingPushed();
    }

    public function test_ensure_processor_running_recovers_and_wakes_when_heartbeat_is_stale_and_no_lock_is_held(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardQueueState([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $result = resolve(BattleRewardProcessingQueueManager::class)->ensureProcessorRunning($character->id);

        $this->assertTrue($result);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_ensure_processor_running_returns_false_without_throwing_when_queue_state_cannot_be_resolved(): void
    {
        Event::fake();
        Queue::fake();
        // A character id with no backing Character row violates the queue state
        // table's foreign key; MySQL's INSERT IGNORE silently skips that row
        // instead of raising an error, so no queue state row ever exists for it.
        $nonExistentCharacterId = 999999999;

        $result = resolve(BattleRewardProcessingQueueManager::class)->ensureProcessorRunning($nonExistentCharacterId);

        $this->assertFalse($result);
        Queue::assertNothingPushed();
    }

    public function test_enqueue_returns_a_successful_result_containing_the_created_request(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $result = resolve(BattleRewardProcessingQueueManager::class)->enqueue(
            $character,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            'battle:'.$character->id.':1',
            ['character_id' => $character->id, 'monster_id' => 1, 'context' => []],
        );

        $this->assertTrue($result->successful());
        $this->assertNull($result->failure());
        $this->assertInstanceOf(CharacterBattleRewardRequest::class, $result->request());
        $this->assertSame($character->id, $result->request()->character_id);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_enqueue_retry_exhaustion_with_a_persistent_lock_error_returns_a_failed_result_instead_of_throwing(): void
    {
        // Event::fake() swaps Model::$dispatcher to a fake that does not invoke
        // registered `creating()` listeners, which would silently defeat the
        // forced QueryException below. Queue::fake() alone does not touch the
        // Eloquent event dispatcher, so it is kept to prevent a real dispatch.
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        CharacterBattleRewardRequest::creating(function (): void {
            throw new QueryException('mysql', 'insert into character_battle_reward_requests', [], new PDOException('Lock wait timeout exceeded', 1205));
        });

        $result = resolve(BattleRewardProcessingQueueManager::class)->enqueue(
            $character,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            'battle:'.$character->id.':1',
            ['character_id' => $character->id, 'monster_id' => 1, 'context' => []],
        );

        $this->assertFalse($result->successful());
        $this->assertNull($result->request());
        $this->assertInstanceOf(QueryException::class, $result->failure());
        $this->assertSame(0, CharacterBattleRewardRequest::where('character_id', $character->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_enqueue_retries_and_succeeds_after_one_retryable_lock_error(): void
    {
        // Event::fake() is intentionally omitted here; see the comment in
        // test_enqueue_retry_exhaustion_with_a_persistent_lock_error_returns_a_failed_result_instead_of_throwing().
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $attempts = 0;

        CharacterBattleRewardRequest::creating(function () use (&$attempts): void {
            $attempts++;

            if ($attempts === 1) {
                throw new QueryException('mysql', 'insert into character_battle_reward_requests', [], new PDOException('Deadlock found', 1213));
            }
        });

        $result = resolve(BattleRewardProcessingQueueManager::class)->enqueue(
            $character,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            'battle:'.$character->id.':1',
            ['character_id' => $character->id, 'monster_id' => 1, 'context' => []],
        );

        $this->assertTrue($result->successful());
        $this->assertInstanceOf(CharacterBattleRewardRequest::class, $result->request());
        $this->assertSame(2, $attempts);
        $this->assertSame(1, CharacterBattleRewardRequest::where('character_id', $character->id)->count());
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_enqueue_does_not_retry_a_non_retryable_query_exception(): void
    {
        // Event::fake() is intentionally omitted here; see the comment in
        // test_enqueue_retry_exhaustion_with_a_persistent_lock_error_returns_a_failed_result_instead_of_throwing().
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $attempts = 0;

        CharacterBattleRewardRequest::creating(function () use (&$attempts): void {
            $attempts++;

            throw new QueryException('mysql', 'insert into character_battle_reward_requests', [], new PDOException('Column not found', 1054));
        });

        $result = resolve(BattleRewardProcessingQueueManager::class)->enqueue(
            $character,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            'battle:'.$character->id.':1',
            ['character_id' => $character->id, 'monster_id' => 1, 'context' => []],
        );

        $this->assertFalse($result->successful());
        $this->assertInstanceOf(QueryException::class, $result->failure());
        $this->assertSame(1, $attempts);
        Queue::assertNothingPushed();
    }
}
