<?php

namespace Tests\Feature\Game\Core\Api;

use App\Flare\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNotification;
use Tests\Setup\CharacterSetup;


class NotificationsControllerTest extends TestCase {

    // use RefreshDatabase,
    //     CreateItem,
    //     CreateUser,
    //     CreateNotification;

    // private $character;

    // public function setUp(): void {
    //     parent::setUp();

    //     $this->character = (new CharacterSetup)->setupCharacter($this->createUser())
    //                                            ->setSkill('Looting', [])
    //                                            ->setSkill('Weapon Crafting', [])
    //                                            ->getCharacter();
    // }

    // public function tearDown(): void {
    //     parent::tearDown();

    //     $this->character = null;
    // }

    // public function testHasNotifications() {
    //     $this->createNotification([
    //         'character_id' => $this->character->id,
    //         'title'        => 'Sample',
    //         'message'      => 'Sample',
    //         'status'       => 'success',
    //         'type'         => 'adventure',
    //         'read'         => false,
    //         'url'          => 'somthing.com',
    //     ]);

    //     $response = $this->actingAs($this->character->user, 'api')
    //                      ->json('GET', '/api/notifications')
    //                      ->response;

    //     $content = json_decode($response->content());

    //     $this->assertEquals(200, $response->status());
    //     $this->assertNotEmpty($content);
    //     $this->assertEquals($content[0]->status, 'success');
    // }

    // public function testHasNoNotifications() {
    //     $this->createNotification([
    //         'character_id' => $this->character->id,
    //         'title'        => 'Sample',
    //         'message'      => 'Sample',
    //         'status'       => 'success',
    //         'type'         => 'adventure',
    //         'read'         => true,
    //         'url'          => 'somthing.com',
    //     ]);


    //     $response = $this->actingAs($this->character->user, 'api')
    //                      ->json('GET', '/api/notifications')
    //                      ->response;

    //     $content = json_decode($response->content());

    //     $this->assertEquals(200, $response->status());
    //     $this->assertEmpty($content);
    // }

    // public function testClearAllNotifications() {
    //     $this->createNotification([
    //         'character_id' => $this->character->id,
    //         'title'        => 'Sample',
    //         'message'      => 'Sample',
    //         'status'       => 'success',
    //         'type'         => 'adventure',
    //         'read'         => false,
    //         'url'          => 'somthing.com',
    //     ]);

    //     $response = $this->actingAs($this->character->user, 'api')
    //                      ->json('POST', '/api/notifications/clear')
    //                      ->response;

    //     $this->assertEquals(200, $response->status());
    //     $this->assertTrue(
    //         $this->character->notifications()->where('read', false)->get()->isEmpty()
    //     );
    // }

    // public function testClearNotification() {
    //     $this->createNotifications([
    //         'character_id' => $this->character->id,
    //         'title'        => 'Sample',
    //         'message'      => 'Sample',
    //         'status'       => 'success',
    //         'type'         => 'adventure',
    //         'read'         => false,
    //         'url'          => 'somthing.com',
    //     ], 2);

    //     $response = $this->actingAs($this->character->user, 'api')
    //                      ->json('POST', '/api/notifications/1/clear')
    //                      ->response;

    //     $this->assertEquals(200, $response->status());
    //     $this->assertEquals(
    //         $this->character->notifications()->where('read', false)->count(), 1
    //     );
    // }

    // public function testFailToClearNotification() {
    //     $character = (new CharacterSetup)->setupCharacter($this->createUser())
    //                                      ->setSkill('Looting', [])
    //                                      ->setSkill('Weapon Crafting', [])
    //                                      ->getCharacter();

    //     $this->createNotifications([
    //         'character_id' => $character->id,
    //         'title'        => 'Sample',
    //         'message'      => 'Sample',
    //         'status'       => 'success',
    //         'type'         => 'adventure',
    //         'read'         => false,
    //         'url'          => 'somthing.com',
    //     ], 2);

    //     $response = $this->actingAs($this->character->user, 'api')
    //                      ->json('POST', '/api/notifications/1/clear')
    //                      ->response;

    //     $this->assertEquals(422, $response->status());
    //     $this->assertEquals(
    //         json_decode($response->content())->error, 'Invalid input.'
    //     );
    // }
}
