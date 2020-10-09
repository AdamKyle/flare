<?php

namespace Tests\Feature\Game\Maps\Adventure;

use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Setup\CharacterSetup;

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

        $this->seed(GameSkillsSeeder::class);

        Queue::fake();

        $this->adventure = $this->createNewAdventure();
        $this->character = (new CharacterSetup)->setupCharacter($this->createUser())
                                               ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->adventure = null;
        $this->character = null;
    }

    public function testGetAdventureDetails() {
        $this->actingAs($this->character->user)
                    ->visitRoute('map.adventures.adventure', [
                        'adventure' => $this->adventure->id,
                    ])
                    ->see($this->adventure->name); 
    }

    public function testEmbarkAdventure() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', 'api/character/'.$this->character->id.'/adventure/' . $this->adventure->id, [
                             'levels_at_a_time' => 'all'
                         ])
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Adventure has started!", $content->message);
    }

    public function testEmbarkAlreadyStartedAdventure() {
        $this->createLog($this->character, $this->adventure, false, 1);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', 'api/character/'.$this->character->id.'/adventure/' . $this->adventure->id, [
                             'levels_at_a_time' => 'all'
                         ])
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Adventure has started!", $content->message);
    }

    public function testCannotAdvenutreWhenOneIsInProgress() {

        // Set up an inprogress adventure:
        $this->character->update([
            'can_attack'             => false,
            'can_move'               => false,
            'can_craft'              => false,
            'can_adventure'          => false,
            'can_adventure_again_at' => now()->addMinutes(10),
        ]);

        $this->createLog($this->character, $this->adventure, true, 1);

        $response = $this->actingAs($this->character->user, 'api')
                            ->json('POST', 'api/character/'.$this->character->id.'/adventure/' . $this->adventure->id, [
                                'levels_at_a_time' => 'all'
                            ])
                            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        
        $this->assertEquals('You are adventuring, you cannot do that.', $content->error);
    }

    public function testCancelAdventure() {

        // Set up an inprogress adventure:
        $this->character->update([
            'can_attack'             => false,
            'can_move'               => false,
            'can_craft'              => false,
            'can_adventure'          => false,
            'can_adventure_again_at' => now()->addMinutes(10),
        ]);

        $this->createLog($this->character, $this->adventure, true, 1);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', 'api/character/'.$this->character->id.'/adventure/' . $this->adventure->id . '/cancel')
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Adventure canceled.", $content->message);

        $this->assertTrue($this->character->refresh()->can_attack);
        $this->assertNull($this->character->refresh()->can_adventure_again_at);
    }
}
