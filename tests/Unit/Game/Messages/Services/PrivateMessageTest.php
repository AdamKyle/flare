<?php

namespace Tests\Unit\Game\Messages\Services;

use App\Flare\Models\User;
use App\Flare\Values\NpcTypes;
use App\Game\Messages\Events\NPCMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Models\Message;
use App\Game\Messages\Services\PrivateMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class PrivateMessageTest extends TestCase
{
    use CreateMessage, CreateNpc, CreateRole, CreateUser, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?PrivateMessage $privateMessageService;

    private ?User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->privateMessageService = new PrivateMessage;
        $this->admin = $this->createAdmin($this->createAdminRole());
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->privateMessageService = null;
        $this->admin = null;
    }

    public function testSendPrivateMessageToCharacter()
    {
        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $this->privateMessageService->sendPrivateMessage($character->name, 'Test message');

        $this->assertCount(1, Message::all());
    }

    public function testSendMessageToConjurerNPC()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $npc = $this->createNpc([
            'type' => NpcTypes::SUMMONER,
        ]);

        $this->privateMessageService->sendPrivateMessage($npc->name, 'Test message');

        Event::assertDispatched(NPCMessageEvent::class);
    }

    public function testSendMessageToKingdomHolder()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $npc = $this->createNpc([
            'type' => NpcTypes::KINGDOM_HOLDER,
        ]);

        $this->privateMessageService->sendPrivateMessage($npc->name, 'Test message');

        Event::assertDispatched(NPCMessageEvent::class);
    }

    public function testSendMessageToEntrancetress()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $npc = $this->createNpc([
            'type' => NpcTypes::SPECIAL_ENCHANTS,
        ]);

        $this->privateMessageService->sendPrivateMessage($npc->name, 'Test message');

        Event::assertDispatched(NPCMessageEvent::class);
    }

    public function testSendMessageToQuestGiver()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $npc = $this->createNpc([
            'type' => NpcTypes::QUEST_GIVER,
        ]);

        $this->privateMessageService->sendPrivateMessage($npc->name, 'Test message');

        Event::assertDispatched(NPCMessageEvent::class);
    }

    public function testHaveNoIdeaWhoToSendTo()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Auth::login($character->user);

        $this->privateMessageService->sendPrivateMessage('random name', 'Test message');

        Event::assertDispatched(ServerMessageEvent::class);
    }
}
