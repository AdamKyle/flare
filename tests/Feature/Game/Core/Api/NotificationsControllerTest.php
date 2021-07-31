<?php

namespace Tests\Feature\Game\Core\Api;

use App\Flare\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNotification;


class NotificationsControllerTest extends TestCase {

    use RefreshDatabase,
        CreateItem,
        CreateUser,
        CreateNotification;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testHasNotifications() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $this->createNotification([
            'character_id' => $character->id,
            'title'        => 'Sample',
            'message'      => 'Sample',
            'status'       => 'success',
            'type'         => 'adventure',
            'read'         => false,
            'url'          => 'somthing.com',
        ]);

        $response = $this->actingAs($user)
                         ->json('GET', '/api/notifications')
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
        $this->assertEquals($content[0]->status, 'success');
        $this->assertTrue($character->refresh()->notifications->isNotEmpty());
    }

    public function testHasNoNotifications() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $this->createNotification([
            'character_id' => $character->id,
            'title'        => 'Sample',
            'message'      => 'Sample',
            'status'       => 'success',
            'type'         => 'adventure',
            'read'         => true,
            'url'          => 'somthing.com',
        ]);


        $response = $this->actingAs($user)
                         ->json('GET', '/api/notifications')
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEmpty($content);
    }

    public function testClearAllNotifications() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $this->createNotification([
            'character_id' => $character->id,
            'title'        => 'Sample',
            'message'      => 'Sample',
            'status'       => 'success',
            'type'         => 'adventure',
            'read'         => false,
            'url'          => 'somthing.com',
        ]);

        $response = $this->actingAs($user)
                         ->json('POST', '/api/notifications/clear')
                         ->response;

        $this->assertEquals(200, $response->status());
        $this->assertTrue(
            $character->refresh()->notifications()->where('read', false)->get()->isEmpty()
        );
    }

    public function testClearNotification() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $this->createNotifications([
            'character_id' => $character->id,
            'title'        => 'Sample',
            'message'      => 'Sample',
            'status'       => 'success',
            'type'         => 'adventure',
            'read'         => false,
            'url'          => 'somthing.com',
        ], 2);

        $notificationId = Notification::first()->id;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/notifications/'.$notificationId.'/clear')
                         ->response;

        $this->assertEquals(200, $response->status());
        $this->assertEquals(
            $character->refresh()->notifications()->where('read', false)->count(), 1
        );
    }

    public function testFailToClearNotification() {
        $character = $this->character->getCharacter();

        $this->createNotifications([
            'character_id' => $character->id,
            'title'        => 'Sample',
            'message'      => 'Sample',
            'status'       => 'success',
            'type'         => 'adventure',
            'read'         => false,
            'url'          => 'somthing.com',
        ], 2);

        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $notificationId = Notification::first()->id;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/notifications/'.$notificationId.'/clear')
                         ->response;

        $this->assertEquals(422, $response->status());
        $this->assertEquals(
            json_decode($response->content())->error, 'Invalid input.'
        );
    }
}
