<?php

namespace Tests\Unit\Game\Messages\Events;

use App\Game\Messages\Events\MessageSentEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMessage;

class MessageSentEventTest extends TestCase
{
    use CreateMessage, RefreshDatabase;

    private ?CharacterFactory $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testSendMessageThroughMessageSentEvent()
    {
        Event::fake();

        $user = $this->character->getUser();
        $character = $this->character->getCharacter();

        $message = $this->createMessage($user, [
            'message' => 'Test Message',
            'from_user' => null,
            'to_user' => null,
            'x_position' => $character->map->position_x,
            'y_position' => $character->map->position_y,
            'color' => '#000',
            'hide_location' => false,
        ]);

        event(new MessageSentEvent($user, $message));

        Event::assertDispatched(MessageSentEvent::class);
    }
}
