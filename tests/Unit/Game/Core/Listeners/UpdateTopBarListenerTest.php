<?php

namespace Tests\Unit\Game\Core\Listeners;

use App\Flare\Transformers\CharacterTopBarTransformer;
use App\Game\Core\Events\UpdateTopBarBroadcastEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Listeners\UpdateTopBarListener;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class UpdateTopBarListenerTest extends TestCase
{
    use RefreshDatabase;

    public function testListenerBroadcastsExactlyItsCurrentTopBarPayload(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $expected = resolve(Manager::class)->createData(
            new Item($character, resolve(CharacterTopBarTransformer::class))
        )->toArray();

        Event::fake([UpdateTopBarBroadcastEvent::class]);

        resolve(UpdateTopBarListener::class)->handle(new UpdateTopBarEvent($character));

        Event::assertDispatched(UpdateTopBarBroadcastEvent::class, function (UpdateTopBarBroadcastEvent $event) use ($character, $expected): bool {
            $channel = $event->broadcastOn();

            $this->assertSame($expected, $event->characterSheet);
            $this->assertInstanceOf(PrivateChannel::class, $channel);
            $this->assertSame('private-update-top-bar-'.$character->user_id, $channel->name);

            return true;
        });
        Event::assertDispatchedTimes(UpdateTopBarBroadcastEvent::class, 1);
    }

    public function testListenerPreservesCurrencyAndEquipmentDependentPayload(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->equipStartingEquipment()
            ->getCharacter();
        $character->update([
            'gold' => 123456,
            'gold_dust' => 234567,
            'shards' => 345678,
            'copper_coins' => 456789,
        ]);
        $character = $character->refresh();
        $expected = resolve(Manager::class)->createData(
            new Item($character, resolve(CharacterTopBarTransformer::class))
        )->toArray();

        Event::fake([UpdateTopBarBroadcastEvent::class]);

        resolve(UpdateTopBarListener::class)->handle(new UpdateTopBarEvent($character));

        Event::assertDispatched(UpdateTopBarBroadcastEvent::class, function (UpdateTopBarBroadcastEvent $event) use ($expected): bool {
            $this->assertSame($expected, $event->characterSheet);

            return true;
        });
        Event::assertDispatchedTimes(UpdateTopBarBroadcastEvent::class, 1);
    }
}
