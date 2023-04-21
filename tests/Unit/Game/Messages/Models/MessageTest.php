<?php

namespace Tests\Unit\Game\Messages\Models;

use App\Flare\Models\CharacterMercenary;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Mercenaries\Services\MercenaryService;
use App\Game\Mercenaries\Values\ExperienceBuffValue;
use App\Game\Mercenaries\Values\MercenaryValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMessage;

class MessageTest extends TestCase {

    use RefreshDatabase, CreateMessage;

    private ?CharacterFactory $character;

    public function setUp(): void {
        parent::setUp();

        $this->character        = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character        = null;
    }

    public function testCreateMessage() {
        $user = $this->character->getUser();
        $character = $this->character->getCharacter();

        $message = $this->createMessage($user, [
            'message'       => 'Test Message',
            'from_user'     => null,
            'to_user'       => null,
            'x_position'    => $character->map->position_x,
            'y_position'    => $character->map->position_y,
            'color'         => '#000',
            'hide_location' => false,
        ]);

        $this->assertEquals($user->character->name, $message->user->character->name);
        $this->assertEquals('Test Message', $message->message);
        $this->assertEquals($character->map->position_x, $message->x_position);
        $this->assertEquals($character->map->position_y, $message->y_position);
        $this->assertEquals('#000', $message->color);
        $this->assertEquals(false, $message->hide_location);
    }

    public function testSendMessageToAnotherUser() {
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

        $this->assertEquals($user->character->name, $message->user->character->name);
        $this->assertEquals('Test Message', $message->message);
        $this->assertEquals($character->map->position_x, $message->x_position);
        $this->assertEquals($character->map->position_y, $message->y_position);
        $this->assertEquals('#000', $message->color);
        $this->assertEquals(false, $message->hide_location);
        $this->assertEquals($user->id, $message->toUser->id);
        $this->assertEquals($user->id, $message->fromUser->id);
    }
}
