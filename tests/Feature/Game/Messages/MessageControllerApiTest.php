<?php

namespace Tests\Feature\Game\Messages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class MessageControllerApiTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter;

    public function setUp(): void {
        parent::setUp();

        Event::fake();
    }

    public function testFetchUserInfo() {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'api')
                         ->json('GET', '/api/user-chat-info/' . $user->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertFalse($content->user->is_silenced);
    }

    public function testUserCanSendMessage() {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $user = $this->createUser();

        $character = $this->createCharacter([
            'name' => 'Sample',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'api')
                         ->json('POST', '/api/public-message', [
                             'message' => 'sample'
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testWhenNotLoggedInCannotSendMessage() {
        $response = $this->json('POST', '/api/public-message', [
                             'message' => 'sample'
                         ])
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    public function testGetServerMesssageForType() {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'api')
                         ->json('GET', '/api/server-message', [
                             'type' => 'message_length_0'
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }


    public function testWhenNotLoggedInCannotAccessServerMessage() {
        $response = $this->json('GET', '/api/server-message', [
                             'type' => 'message_length_0'
                         ])
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    public function testSendPrivateMesssage() {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $user = $this->createUser();

        $character = $this->createCharacter([
            'name' => 'Sample',
            'user_id' => $user->id,
        ]);

        $userSecond = $this->createUser();

        $characterSecond = $this->createCharacter([
            'name' => 'Sample2',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'api')
                         ->json('POST', '/api/private-message', [
                             'message' => 'sample',
                             'user_name' => $characterSecond->name
                         ])
                         ->response;

        $this->assertEquals($user->messages->first()->fromUser->id, $user->id);
        $this->assertEquals($user->messages->first()->toUser->id, $characterSecond->user->id);
        $this->assertEquals($user->messages->first()->message, 'sample');

        $this->assertEquals(200, $response->status());
    }

    public function testCannotFindPlayerForPrivateMesssage() {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $user = $this->createUser();

        $character = $this->createCharacter([
            'name' => 'Sample',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'api')
                         ->json('POST', '/api/private-message', [
                             'message' => 'sample',
                             'user_name' => 'Gorge'
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testNotLoggedIn() {

        $response = $this->json('POST', '/api/private-message', [
                             'message' => 'sample',
                             'user_name' => 'Gorge'
                         ])
                         ->response;

        $this->assertEquals(401, $response->status());
    }
}
