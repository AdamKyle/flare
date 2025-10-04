<?php

namespace Tests\Unit\Game\Messages\Events;

use App\Game\Messages\Events\PrivateMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class PrivateMessageEventTest extends TestCase
{
    use CreateMessage, CreateRole, CreateUser, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_send_private_message_with_private_message_event()
    {
        Event::fake();

        $user = $this->character->getUser();
        $character = $this->character->getCharacter();

        $message = $this->createMessage($user, [
            'message' => 'Test Message',
            'from_user' => $user->id,
            'to_user' => $user->id,
            'x_position' => $character->map->position_x,
            'y_position' => $character->map->position_y,
            'color' => '#000',
            'hide_location' => false,
        ]);

        event(new PrivateMessageEvent($user, $user, $message));

        Event::assertDispatched(PrivateMessageEvent::class);
    }

    public function test_send_private_message_as_admin_with_private_message_event()
    {
        Event::fake();

        $user = $this->createAdmin($this->createAdminRole());
        $character = $this->character->getCharacter();

        $message = $this->createMessage($user, [
            'message' => 'Test Message',
            'from_user' => $user->id,
            'to_user' => $character->user->id,
            'x_position' => $character->map->position_x,
            'y_position' => $character->map->position_y,
            'color' => '#000',
            'hide_location' => false,
        ]);

        event(new PrivateMessageEvent($user, $character->user, $message));

        Event::assertDispatched(function (PrivateMessageEvent $event) {
            return $event->from === 'The Creator';
        });
    }
}
