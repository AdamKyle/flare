<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Kingdoms\Service\AbandonKingdomService;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Unit\Game\Kingdoms\Helpers\CreateKingdomHelper;

class AbandonKingdomServiceTest extends TestCase {

    use CreateGameBuilding, CreateKingdomHelper, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?AbandonKingdomService $abandonKingdomService;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->abandonKingdomService = resolve(AbandonKingdomService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->abandonKingdomService = null;
    }

    public function testShouldAbandonKingdom() {
        Event::fake();
        Queue::fake();

        $kingdom = $this->createKingdomForCharacter($this->character);

        $character = $this->character->getCharacter();

        $this->abandonKingdomService->setKingdom($kingdom)->abandon();

        $kingdom = $kingdom->refresh();

        $character = $character->refresh();

        $this->assertTrue($kingdom->npc_owned);
        $this->assertNull($kingdom->character_id);
        $this->assertEquals(0.01, $kingdom->current_morale);

        Event::assertDispatched(GlobalMessageEvent::class, function ($event) use ($kingdom) {
            return $event->message === 'A kingdom has fallen into the rubble at (X/Y): ' .
                $kingdom->x_position . '/' . $kingdom->y_position . ' on the: ' .
                $kingdom->gameMap->name . ' plane.';
        });

        Event::assertDispatched(ServerMessageEvent::class, function ($event) use ($kingdom) {
            return $event->message === $kingdom->name . ' Has been given to the NPC due to being abandoned, at Location (x/y): '
                . $kingdom->x_position . '/' . $kingdom->y_position . ' on the: ' . $kingdom->gameMap->name . ' plane.';
        });

        $this->assertNotNull($character->can_settle_again_at);
    }

    public function testShouldAbandonKingdomWhileCanSettleAgainAtIsNotNull() {
        Event::fake();
        Queue::fake();

        $kingdom = $this->createKingdomForCharacter($this->character);

        $character = $this->character->getCharacter();

        $character->update([
            'can_settle_again_at' => now()->addMinutes(15),
        ]);

        $character->refresh();

        $this->abandonKingdomService->setKingdom($kingdom)->abandon();

        $kingdom = $kingdom->refresh();

        $character = $character->refresh();

        $this->assertTrue($kingdom->npc_owned);
        $this->assertNull($kingdom->character_id);
        $this->assertEquals(0.01, $kingdom->current_morale);

        Event::assertDispatched(GlobalMessageEvent::class, function ($event) use ($kingdom) {
            return $event->message === 'A kingdom has fallen into the rubble at (X/Y): ' .
                $kingdom->x_position . '/' . $kingdom->y_position . ' on the: ' .
                $kingdom->gameMap->name . ' plane.';
        });

        Event::assertDispatched(ServerMessageEvent::class, function ($event) use ($kingdom) {
            return $event->message === $kingdom->name . ' Has been given to the NPC due to being abandoned, at Location (x/y): '
                . $kingdom->x_position . '/' . $kingdom->y_position . ' on the: ' . $kingdom->gameMap->name . ' plane.';
        });

        $this->assertGreaterThan(15, now()->diffInMinutes($character->can_settle_again_at));
    }
}
