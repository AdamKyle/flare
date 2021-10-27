<?php

namespace Tests\Feature\Game\Messages;

use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Models\Message;
use App\Game\Messages\Values\MapChatColor;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class MessageControllerApiTest extends TestCase
{
    use RefreshDatabase, CreateRole, CreateUser, CreateNpc, CreateMessage, CreateCelestials, CreateMonster, CreateItem;

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

    public function testFetchMessagesForSur() {
        $user = $this->character->getUser();

        $this->createMessage($user, [
            'color' => '#ffffff'
        ]);

        $response = $this->actingAs($user)
            ->json('GET', '/api/last-chats/')
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals('SUR', $content[0]->map);
    }

    public function testFetchMessagesForLaby() {
        $user = $this->character->getUser();

        $this->createMessage($user, [
            'color' => '#ffad47'
        ]);

        $response = $this->actingAs($user)
            ->json('GET', '/api/last-chats/')
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals('LABY', $content[0]->map);
    }

    public function testFetchMessagesForDUN() {
        $user = $this->character->getUser();

        $this->createMessage($user, [
            'color' => '#ccb9a5'
        ]);

        $response = $this->actingAs($user)
            ->json('GET', '/api/last-chats/')
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals('DUN', $content[0]->map);
    }

    public function testFetchMessagesForShadowPlane() {
        $user = $this->character->getUser();

        $this->createMessage($user, [
            'color' => '#ababab'
        ]);

        $response = $this->actingAs($user)
            ->json('GET', '/api/last-chats/')
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals('SHP', $content[0]->map);
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

    public function testUserCanSendMessageFromSur() {
        $character = $this->character->getCharacter(false);

        $character->map->gameMap()->update([
            'name' => 'Surface'
        ]);

        $user = $character->refresh()->user;


        $response = $this->actingAs($user)
            ->json('POST', '/api/public-message', [
                'message' => 'sample'
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        $this->assertEquals(MapChatColor::SURFACE, Message::first()->color);
    }

    public function testUserCanSendMessageFromLaby() {
        $character = $this->character->getCharacter(false);

        $character->map->gameMap()->update([
            'name' => 'Labyrinth'
        ]);

        $user = $character->refresh()->user;


        $response = $this->actingAs($user)
            ->json('POST', '/api/public-message', [
                'message' => 'sample'
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        $this->assertEquals(MapChatColor::LABYRINTH, Message::first()->color);
    }

    public function testUserCanSendMessageFromDungeons() {
        $character = $this->character->getCharacter(false);

        $character->map->gameMap()->update([
            'name' => 'Dungeons'
        ]);

        $user = $character->refresh()->user;


        $response = $this->actingAs($user)
            ->json('POST', '/api/public-message', [
                'message' => 'sample'
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        $this->assertEquals(MapChatColor::DUNGEONS, Message::first()->color);
    }

    public function testUserCanSendMessageFromShadowPlane() {
        $character = $this->character->getCharacter(false);

        $character->map->gameMap()->update([
            'name' => 'Shadow Plane'
        ]);

        $user = $character->refresh()->user;


        $response = $this->actingAs($user)
            ->json('POST', '/api/public-message', [
                'message' => 'sample'
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        $this->assertEquals(MapChatColor::SHP, Message::first()->color);
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
                             'user_name' => $character->getCharacter(false)->name
                         ])
                         ->response;

        $user      = $this->character->getUser();
        $character = $character->getCharacter(false);

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

    public function testCannotUsePCTCommandWhenCannotMove() {
        $this->character->updateCharacter([
            'can_move' => false,
        ]);

        $this->actingAs($this->character->getUser());

        Event::fake();

        $response = $this->json('POST', '/api/public-entity', [
            'attempt_to_teleport' => false
        ])->response;

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals(200, $response->status());
    }

    public function testCannotUsePCTCommandWhenNoCelestials() {
        $this->actingAs($this->character->getUser());

        Event::fake();

        $response = $this->json('POST', '/api/public-entity', [
            'attempt_to_teleport' => false
        ])->response;

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals(200, $response->status());
    }

    public function testCannotUseTeleportFromPCTWhenNoQuestItem() {
        $this->actingAs($this->character->getUser());

        $this->createCelestialFight([
            'monster_id'      => $this->createMonster([
                'game_map_id' => $this->character->getCharacter(false)->map->gameMap->id
            ])->id,
            'character_id'    => $this->character->getCharacter(false)->id,
            'conjured_at'     => now(),
            'x_position'      => 16,
            'y_position'      => 36,
            'damaged_kingdom' => false,
            'stole_treasury'  => false,
            'weakened_morale' => false,
            'current_health'  => 1,
            'max_health'      => 1,
            'type'            => CelestialConjureType::PRIVATE,
        ]);

        Event::fake();

        $response = $this->json('POST', '/api/public-entity', [
            'attempt_to_teleport' => true
        ])->response;

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals(200, $response->status());
    }

    public function testCanUseTeleportFromPCT() {

        $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::TELEPORT_TO_CELESTIAL
        ]));

        $this->actingAs($this->character->getUser());

        $this->createCelestialFight([
            'monster_id'      => $this->createMonster([
                'game_map_id' => $this->character->getCharacter(false)->map->gameMap->id
            ])->id,
            'character_id'    => $this->character->getCharacter(false)->id,
            'conjured_at'     => now(),
            'x_position'      => 16,
            'y_position'      => 36,
            'damaged_kingdom' => false,
            'stole_treasury'  => false,
            'weakened_morale' => false,
            'current_health'  => 1,
            'max_health'      => 1,
            'type'            => CelestialConjureType::PRIVATE,
        ]);

        // We don't have actual maps, so lets fake the water.
        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        Event::fake();

        $response = $this->json('POST', '/api/public-entity', [
            'attempt_to_teleport' => true
        ])->response;

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals(200, $response->status());
    }
}
