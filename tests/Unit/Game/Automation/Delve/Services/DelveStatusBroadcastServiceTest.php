<?php

namespace Tests\Unit\Game\Automation\Delve\Services;

use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Automation\Calculations\BattleMessageTotalsCalculator;
use App\Game\Automation\Delve\Events\DelveStatusUpdated;
use App\Game\Automation\Delve\Services\DelveStatusBroadcastService;
use App\Game\Automation\Delve\Services\DelveStatusService;
use App\Game\Automation\Delve\Services\DelveTelemetryService;
use App\Game\Core\Items\Enricher\EquippableEnricher;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Transformers\UsableItemTransformer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use League\Fractal\Manager;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateMonster;

class DelveStatusBroadcastServiceTest extends TestCase
{
    use CreateDelveExploration, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?DelveStatusService $delveStatusService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $manager = new Manager;
        $itemTransformer = new ItemTransformer(new ItemEnricherFactory(
            new EquippableEnricher,
            new EquippableItemTransformer,
            new UsableItemTransformer,
            new QuestItemTransformer,
            new PlainDataSerializer,
            $manager,
        ));

        $this->delveStatusService = new DelveStatusService(
            $itemTransformer,
            new DelveTelemetryService(new BattleMessageTotalsCalculator),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();

        $this->character = null;
        $this->delveStatusService = null;
    }

    public function test_broadcast_dispatches_the_delve_status_service_snapshot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-26 12:00:00'));
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subMinutes(12),
            'completed_at' => null,
            'pack_size' => 10,
        ]);

        (new DelveStatusBroadcastService($this->delveStatusService))->broadcast($character);

        $expectedStatus = $this->delveStatusService->statusForCharacter($character);

        Event::assertDispatched(DelveStatusUpdated::class, function (DelveStatusUpdated $event) use ($character, $expectedStatus) {
            return $event->broadcastWith() === [
                'user_id' => $character->user_id,
                'status' => $expectedStatus,
            ];
        });
    }
}
