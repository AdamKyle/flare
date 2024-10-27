<?php

namespace Tests\Feature\Game\Messages\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateMessage;

class FetchMessagesControllerTest extends TestCase
{
    use RefreshDatabase, CreateMessage, CreateAnnouncement;

    private ?CharacterFactory $character = null;

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

    public function testGetLastMessages()
    {
        $character = $this->character->getCharacter();

        $this->createMessage($character->user);
        $this->createAnnouncement();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/last-chats', [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['chat_messages']);
        $this->assertNotEmpty($jsonData['announcements']);
        $this->assertEquals(200, $response->status());
    }
}
