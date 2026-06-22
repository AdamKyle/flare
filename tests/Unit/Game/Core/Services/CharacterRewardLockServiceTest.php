<?php

namespace Tests\Unit\Game\Core\Services;

use App\Game\Core\Services\CharacterRewardLockService;
use Exception;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CharacterRewardLockServiceTest extends TestCase
{
    public function testRunReturnsCallbackResult(): void
    {
        $result = resolve(CharacterRewardLockService::class)->run(123, function (): string {
            return 'completed';
        });

        $this->assertEquals('completed', $result);
    }

    public function testRunUsesCharacterRewardLockKey(): void
    {
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('block')
            ->once()
            ->with(300, Mockery::type('callable'))
            ->andReturn('completed');

        Cache::shouldReceive('lock')
            ->once()
            ->with('character-rewards:456', 300)
            ->andReturn($lock);

        resolve(CharacterRewardLockService::class)->run(456, function (): string {
            return 'completed';
        });
    }

    public function testRunUsesBlockingLockBehaviorForSameCharacter(): void
    {
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('block')
            ->once()
            ->with(300, Mockery::type('callable'))
            ->andReturnUsing(function (int $seconds, callable $callback): string {
                return $callback();
            });

        Cache::shouldReceive('lock')
            ->once()
            ->with('character-rewards:789', 300)
            ->andReturn($lock);

        $result = resolve(CharacterRewardLockService::class)->run(789, function (): string {
            return 'serialized';
        });

        $this->assertEquals('serialized', $result);
    }

    public function testRunReleasesLockAfterCallbackCompletion(): void
    {
        $service = resolve(CharacterRewardLockService::class);

        $service->run(321, function (): void {
        });

        $lock = Cache::lock('character-rewards:321', 300);

        $this->assertTrue($lock->get());

        $lock->release();
    }

    public function testRunReleasesLockAfterCallbackException(): void
    {
        $service = resolve(CharacterRewardLockService::class);

        try {
            $service->run(654, function (): void {
                throw new Exception('Callback failed.');
            });
        } catch (Exception) {
        }

        $lock = Cache::lock('character-rewards:654', 300);

        $this->assertTrue($lock->get());

        $lock->release();
    }

    public function testRunRethrowsCallbackException(): void
    {
        $exception = new Exception('Callback failed.');

        $this->expectExceptionObject($exception);

        resolve(CharacterRewardLockService::class)->run(987, function () use ($exception): void {
            throw $exception;
        });
    }
}
