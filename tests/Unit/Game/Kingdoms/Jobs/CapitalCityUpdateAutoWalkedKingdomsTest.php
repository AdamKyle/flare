<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Jobs\CapitalCityUpdateAutoWalkedKingdoms;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Scope;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateKingdom;

class CapitalCityUpdateAutoWalkedKingdomsTest extends TestCase
{
    use CreateKingdom, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_job_keeps_emergency_retry_and_backoff_configuration(): void
    {
        $job = new CapitalCityUpdateAutoWalkedKingdoms(82);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 60], $job->backoff);
    }

    public function test_failed_logs_original_exception_and_kingdom_identifier(): void
    {
        $exception = new RuntimeException('broadcast transport failed');
        Log::shouldReceive('error')
            ->once()
            ->with('Capital city kingdom update job failed.', Mockery::on(
                fn (array $context): bool => $context['kingdom_id'] === 82
                    && $context['exception'] === $exception,
            ));

        (new CapitalCityUpdateAutoWalkedKingdoms(82))->failed($exception);

        $this->addToAssertionCount(1);
    }

    public function test_broadcast_failure_after_transformation_does_not_fail_or_repeat_transformation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
        ]);
        $transformer = Mockery::mock(KingdomTransformer::class);
        $scope = Mockery::mock(Scope::class);
        $scope->shouldReceive('toArray')->once()->andReturn(['data' => ['id' => $kingdom->id]]);
        $manager = Mockery::mock(Manager::class);
        $manager->shouldReceive('createData')->once()->andReturn($scope);
        Event::listen(UpdateKingdom::class, function (): void {
            throw new RuntimeException('broadcast transport failed');
        });
        Log::shouldReceive('warning')
            ->once()
            ->with('Capital city kingdom update broadcast failed after transformation.', Mockery::on(
                fn (array $context): bool => $context['kingdom_id'] === $kingdom->id
                    && $context['character_id'] === $character->id
                    && $context['exception'] instanceof RuntimeException,
            ));

        (new CapitalCityUpdateAutoWalkedKingdoms($kingdom->id))->handle($transformer, $manager);

        $this->addToAssertionCount(1);
    }
}
