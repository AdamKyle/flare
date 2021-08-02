<?php

namespace Tests\Feature\Game\Messages;

use App\Flare\Values\NpcCommandTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class MessageControllerApiTest extends TestCase
{
    use RefreshDatabase, CreateRole, CreateUser, CreateNpc, CreateMessage;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->createAdmin($role, []);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        Event::fake();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character= null;
    }

    public function testFetchUserInfo() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/user-chat-info/' . $user->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertFalse($content->user->is_silenced);
    }

    public function testUserChattingTooMuch() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('GET', '/api/server-message', [
                'type' => 'chatting_to_much'
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
    }

    public function testFetchMessages() {
        $user = $this->character->getUser();

        $this->createMessage($user);

        $response = $this->actingAs($user)
                         ->json('GET', '/api/last-chats/')
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testUserCanSendMessage() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)
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
        $user = $this->character->getUser();

        $response = $this->actingAs($user)
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
        $user = $this->character->getUser();
        $character = (new CharacterFactory)->createBaseCharacter();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/private-message', [
                             'message' => 'sample',
                             'user_name' => $character->getCharacter()->name
                         ])
                         ->response;

        $user      = $this->character->getUser();
        $character = $character->getCharacter();

        $this->assertEquals($user->messages->first()->fromUser->id, $user->id);
        $this->assertEquals($user->messages->first()->toUser->id, $character->user->id);
        $this->assertEquals($user->messages->first()->message, 'sample');

        $this->assertEquals(200, $response->status());
    }

    public function testNpcMessage() {
        $user = $this->character->getUser();
        $npc = $this->createNpc([
            'name'        => 'Apples',
            'real_name'   => 'Apples',
            'game_map_id' => $user->character->map->game_map_id,
        ]);

        $npc->commands()->create([
            'npc_id'       => $npc->id,
            'command'      => 'Take Kingdom',
            'command_type' => NpcCommandTypes::TAKE_KINGDOM,
        ]);

        $response = $this->actingAs($user)
            ->json('POST', '/api/private-message', [
                'message'   => 'Take Kingdom',
                'user_name' => $npc->name,
            ])
            ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testNpcMessageCommandDoesntExist() {
        $user = $this->character->getUser();
        $npc = $this->createNpc([
            'name'        => 'Apples',
            'real_name'   => 'Apples',
            'game_map_id' => $user->character->map->game_map_id,
        ]);

        $npc->commands()->create([
            'npc_id'       => $npc->id,
            'command'      => 'Take Kingdom',
            'command_type' => NpcCommandTypes::TAKE_KINGDOM,
        ]);

        $response = $this->actingAs($user)
            ->json('POST', '/api/private-message', [
                'message'   => 'sample',
                'user_name' => $npc->name,
            ])
            ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCannotFindPlayerForPrivateMesssage() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)
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
