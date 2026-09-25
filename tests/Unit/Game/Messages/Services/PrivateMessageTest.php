<?php

namespace Tests\Unit\Game\Messages\Services;

use App\Flare\Models\User;
use App\Game\Messages\Events\NPCMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Models\Message;
use App\Game\Messages\Services\PrivateMessage;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserSession;

class PrivateMessageTest extends TestCase
{
    use CreateMessage, CreateNpc, CreateRole, CreateUser, CreateUserSession, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?PrivateMessage $privateMessageService;

    private ?User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->privateMessageService = new PrivateMessage;
        $this->admin = $this->createAdmin($this->createAdminRole());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->privateMessageService = null;
        $this->admin = null;
    }

    public function test_send_private_message_to_character()
    {
        $character = $this->character->getCharacter();

        $this->createUserSession($character->user);

        Auth::login($character->user);

        $delivered = $this->privateMessageService->sendPrivateMessage($character->name, 'Test message');

        $messages = Message::where('from_user', $character->user->id)
            ->where('to_user', $character->user->id)
            ->where('message', 'Test message')
            ->get();

        $this->assertTrue($delivered);
        $this->assertCount(1, $messages);
        $this->assertSame($character->user->id, $messages->first()->user_id);
        $this->assertSame($character->user->id, $messages->first()->from_user);
        $this->assertSame($character->user->id, $messages->first()->to_user);
        $this->assertSame('Test message', $messages->first()->message);
    }

    public function test_private_message_is_not_delivered_when_character_is_offline()
    {
        Event::fake([ServerMessageEvent::class]);

        $sender = $this->character->getCharacter();
        $recipient = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Auth::login($sender->user);

        $delivered = $this->privateMessageService->sendPrivateMessage($recipient->name, 'Offline message');

        $this->assertFalse($delivered);
        $this->assertSame(0, Message::where('message', 'Offline message')->count());

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($sender) {
            return $event->broadcastOn()->name === 'private-server-message-'.$sender->user->id
                && $event->message === 'This character is not online, your message was not delivered.';
        });
    }

    public function test_send_message_to_conjurer_npc()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $npc = $this->createNpc([
            'type' => NpcType::SUMMONER->value,
        ]);

        $delivered = $this->privateMessageService->sendPrivateMessage($npc->name, 'Test message');

        $this->assertTrue($delivered);

        Event::assertDispatched(NPCMessageEvent::class);
    }

    public function test_send_message_to_kingdom_holder()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $npc = $this->createNpc([
            'type' => NpcType::KINGDOM_HOLDER->value,
        ]);

        $this->privateMessageService->sendPrivateMessage($npc->name, 'Test message');

        Event::assertDispatched(NPCMessageEvent::class);
    }

    public function test_send_message_to_entrancetress()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $npc = $this->createNpc([
            'type' => NpcType::SPECIAL_ENCHANTS->value,
        ]);

        $this->privateMessageService->sendPrivateMessage($npc->name, 'Test message');

        Event::assertDispatched(NPCMessageEvent::class);
    }

    public function test_send_message_to_quest_giver()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $npc = $this->createNpc([
            'type' => NpcType::QUEST_GIVER->value,
        ]);

        $this->privateMessageService->sendPrivateMessage($npc->name, 'Test message');

        Event::assertDispatched(NPCMessageEvent::class);
    }

    public function test_have_no_idea_who_to_send_to()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $delivered = $this->privateMessageService->sendPrivateMessage('random name', 'Test message');

        $this->assertFalse($delivered);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'No Character or NPC exists for: random name';
        });
    }
}
