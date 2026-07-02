<?php

namespace Tests\Unit\Game\Tops;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Tops\Events\CharacterTopsInspectionUpdated;
use App\Game\Tops\Listeners\CharacterTopsUpdateListener;
use App\Game\Tops\Services\BroadcastTopsUpdateService;
use App\Game\Tops\Services\CharacterTopsInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterTopsUpdateListenerTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testCharacterTopsListenerRespondsToUpdateTopBarEvent(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $broadcastTopsUpdateService = Mockery::mock(BroadcastTopsUpdateService::class);
        $broadcastTopsUpdateService
            ->shouldReceive('broadcastCharacterCurrentMonth')
            ->once();

        $characterTopsInspectionService = Mockery::mock(CharacterTopsInspectionService::class);
        $characterTopsInspectionService
            ->shouldReceive('fullProfile')
            ->once()
            ->with($character)
            ->andReturn([
                'overview' => [
                    'name' => $character->name,
                ],
            ]);

        Event::fake();

        (new CharacterTopsUpdateListener(
            $broadcastTopsUpdateService,
            $characterTopsInspectionService,
        ))->handle(new UpdateTopBarEvent($character));

        Event::assertDispatched(CharacterTopsInspectionUpdated::class, function (CharacterTopsInspectionUpdated $event) use ($character): bool {
            return $event->characterId === $character->id;
        });
    }
}
