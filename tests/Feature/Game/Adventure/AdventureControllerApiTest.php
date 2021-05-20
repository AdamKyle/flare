<?php

namespace Tests\Feature\Game\Adventure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Setup\Character\CharacterFactory;

class AdventureControllerApiTest extends TestCase
{
    use RefreshDatabase,
        CreateLocation,
        CreateUser,
        CreateAdventure;

    private $adventure;

    private $character;

    public function setUp(): void {
        parent::setUp();

        Queue::fake();

        $this->adventure = $this->createNewAdventure();

        $this->character = (new CharacterFactory)
                                ->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->adventure = null;
        $this->character = null;
    }

    public function testGetAdventureDetails() {
        $user = $this->character->getUser();

        $this->actingAs($user)
                    ->visitRoute('map.adventures.adventure', [
                        'adventure' => $this->adventure->id,
                    ])
                    ->see($this->adventure->name);
    }

    public function testGetAdventureLogs() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/character/adventure/logs')
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testEmbarkAdventure() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $response = $this->actingAs($user)
                         ->json('POST', 'api/character/'.$character->id.'/adventure/' . $this->adventure->id, [
                             'levels_at_a_time' => 'all'
                         ])
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Adventure has started!", $content->message);
    }

    public function testEmbarkAlreadyStartedAdventure() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $this->createLog($character, $this->adventure, false, 1);

        $response = $this->actingAs($user)
                         ->json('POST', 'api/character/'.$character->id.'/adventure/' . $this->adventure->id, [
                             'levels_at_a_time' => 'all'
                         ])
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Adventure has started!", $content->message);
    }

    public function testCannotAdvenutreWhenOneIsInProgress() {

        $user      = $this->character->getUser();
        $character = $this->character->updateCharacter([
            'can_attack'             => false,
            'can_move'               => false,
            'can_craft'              => false,
            'can_adventure'          => false,
            'can_adventure_again_at' => now()->addMinutes(10),
        ])->getCharacter();

        $this->createLog($character, $this->adventure, true, 1);

        $response = $this->actingAs($user)
                            ->json('POST', 'api/character/'.$character->id.'/adventure/' . $this->adventure->id, [
                                'levels_at_a_time' => 'all'
                            ])
                            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());

        $this->assertEquals('You are adventuring, you cannot do that.', $content->error);
    }

    public function testCancelAdventure() {

        $user      = $this->character->getUser();
        $character = $this->character->updateCharacter([
            'can_attack'             => false,
            'can_move'               => false,
            'can_craft'              => false,
            'can_adventure'          => false,
            'can_adventure_again_at' => now()->addMinutes(10),
        ])->getCharacter();

        $this->createLog($character, $this->adventure, true, 1);

        $response = $this->actingAs($user)
                         ->json('POST', 'api/character/'.$character->id.'/adventure/' . $this->adventure->id . '/cancel')
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Adventure canceled.", $content->message);

        $character = $this->character->getCharacter();

        $this->assertTrue($character->can_attack);
        $this->assertNull($character->can_adventure_again_at);
    }
}
