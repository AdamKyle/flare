<?php

namespace Tests\Unit\Game\Messages\Services;

use App\Flare\Models\Character;
use App\Game\Messages\Models\Message;
use App\Game\Messages\Services\FetchMessages;
use App\Game\Messages\Values\MapChatColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class FetchMessagesTest extends TestCase
{
    use CreateMessage, CreateRole, CreateUser, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?FetchMessages $fetchMessagesService;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->fetchMessagesService = new FetchMessages;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->fetchMessagesService = null;
    }

    public function testMessageMapNameIsSur()
    {
        $character = $this->character->getCharacter();

        $this->createMessageForTest($character, MapChatColor::SURFACE);

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('SUR', $message->map);
    }

    public function testMessageMapNameIsLaby()
    {
        $character = $this->character->getCharacter();

        $this->createMessageForTest($character, MapChatColor::LABYRINTH);

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('LABY', $message->map);
    }

    public function testMessageMapNameIsDUN()
    {
        $character = $this->character->getCharacter();

        $this->createMessageForTest($character, MapChatColor::DUNGEONS);

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('DUN', $message->map);
    }

    public function testMessageMapNameIsHELL()
    {
        $character = $this->character->getCharacter();

        $this->createMessageForTest($character, MapChatColor::HELL);

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('HELL', $message->map);
    }

    public function testMessageMapNameIsShp()
    {
        $character = $this->character->getCharacter();

        $this->createMessageForTest($character, MapChatColor::SHP);

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('SHP', $message->map);
    }

    public function testMessageMapNameIsPURG()
    {
        $character = $this->character->getCharacter();

        $this->createMessageForTest($character, MapChatColor::PURGATORY);

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('PURG', $message->map);
    }

    public function testMessageMapNameIsICE()
    {
        $character = $this->character->getCharacter();

        $this->createMessageForTest($character, MapChatColor::ICE_PLANE);

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('ICE', $message->map);
    }

    public function testMessageMapNameIsDefaultSurface()
    {
        $character = $this->character->getCharacter();

        $this->createMessageForTest($character, '#0001');

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('SUR', $message->map);
    }

    public function testMessageIsAdmin()
    {
        $user = $this->createAdmin($this->createAdminRole());

        $this->createMessage($user, [
            'message' => 'Test Message',
            'from_user' => null,
            'to_user' => null,
            'x_position' => null,
            'y_position' => null,
            'color' => MapChatColor::SURFACE,
            'hide_location' => false,
        ]);

        $message = $this->fetchMessagesService->fetchMessages()->first();

        $this->assertEquals('The Creator', $message->name);
    }

    protected function createMessageForTest(Character $character, string $color): Message
    {
        return $this->createMessage($character->user, [
            'message' => 'Test Message',
            'from_user' => null,
            'to_user' => null,
            'x_position' => $character->map->position_x,
            'y_position' => $character->map->position_y,
            'color' => $color,
            'hide_location' => false,
        ]);
    }
}
