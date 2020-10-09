<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\ItemAffix;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;
use Tests\Traits\CreateAdventure;

class CharacterAdventureControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateAdventure,
        CreateItem,
        CreateUser;

    private $character;

    private $adventure;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);

        $this->adventure = $this->createNewAdventure();

        $item = $this->createItem([
            'name' => 'Spear',
            'base_damage' => 6,
            'type' => 'weapon',
            'crafting_type' => 'weapon',
        ]);

        $this->character = (new CharacterSetup())
            ->setupCharacter($this->createUser())
            ->setSkill('Looting', [], [
                'level' => 0,
                'xp_towards' => 0.10,
                'currently_training' => true,
            ])
            ->getCharacter();


        $skill = $this->character->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                 ->where('name', 'Looting'); 
        })->first();

        $this->character->adventureLogs()->create([
            'character_id'         => $this->character->id,
            'adventure_id'         => $this->adventure->id,
            'complete'             => true,
            'in_progress'          => false,
            'last_completed_level' => 1,
            'logs'                 => 
            [
                "vcCBZhAOqy3Dg9V6a1MRWCthCGFNResjhH7ttUsFFpREdVoH9oNqyrjVny3cX8McbjyGHZYeJ8txcTov" => [
                    [
                        [
                        "attacker" => "Kyle Adams",
                        "defender" => "Goblin",
                        "messages" => [
                            "Kyle Adams hit for 30",
                        ],
                        "is_monster" => false,
                        ],
                    ],
                ]
            ],
            'rewards'              => 
            [
                "exp" => 100,
                "gold" => 75,
                "items" => [
                  [
                    "id" => $item->id,
                    "name" => $item->name,
                  ],
                ],
                "skill" => [
                  "exp"         => 1000,
                  "skill_name"  => $skill->name,
                  "exp_towards" => $skill->xp_towards,
                ],
              ]
        ]);

        $this->character->refresh();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testCharacterCanSeeLatestAdventure()
    {
        $this->actingAs($this->character->user)
             ->visitRoute('game.current.adventure')
             ->see('Collect Rewards');
    }

    public function testCharacterCanSeeAllAdventures() {
        $this->actingAs($this->character->user)
             ->visitRoute('game.completed.adventures')
             ->see('Previous Adventures')
             ->see($this->adventure->name);
    }

    public function testCharacterCanSeeAllLogsForAdventure() {
        $this->actingAs($this->character->user)
             ->visitRoute('game.completed.adventure', [
                 'adventureLog' => $this->character->adventureLogs->first()->id,
             ])
             ->see($this->adventure->name)
             ->see('Log Entry 1');
    }

    public function testCharacterCanSeeSpecificLogForAdventure() {
        $this->actingAs($this->character->user)
             ->visitRoute('game.completed.adventure.logs', [
                 'adventureLog' => $this->character->adventureLogs->first()->id,
                 'name'         => 'vcCBZhAOqy3Dg9V6a1MRWCthCGFNResjhH7ttUsFFpREdVoH9oNqyrjVny3cX8McbjyGHZYeJ8txcTov'
             ])
             ->see($this->adventure->name)
             ->see('Level: 1');
    }

    public function testCharacterCannotSeeSpecificLogForAdventureInvalidName() {
        $this->actingAs($this->character->user)
             ->visitRoute('game') // come here first, for a place to come back too
             ->visitRoute('game.completed.adventure.logs', [
                 'adventureLog' => $this->character->adventureLogs->first()->id,
                 'name'         => 'xxxx'
             ])
             ->dontSee($this->adventure->name)
             ->dontsee('Level: 1')
             ->see('Invalid input.');
    }

    public function testCharacterCannotSeeLatestAdventure()
    {
        $this->character->adventureLogs->first()->delete();

        $this->actingAs($this->character->user)
             ->visitRoute('game') // So we have some where to redirect back too
             ->visitRoute('game.current.adventure')
             ->see('You have no currently completed adventure. Check your completed adventures for more details.');
    }

    public function testDistributeRewards() {
        $response = $this->actingAs($this->character->user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => 1,
             ]))->response;


        $response->assertSessionHas('success', [
           'You gained a level! Now level: 2',
           'Your skill: Looting gained a level and is now level: 1',
           'You gained the item: Spear',
        ]); 
    }

    public function testCannotDistributeRewardsWhenDead() {
        $this->character->update([
            'is_dead' => true,
        ]);

        $response = $this->actingAs($this->character->user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => 1,
             ]))->response;


        $response->assertSessionHas('error', "You are dead and must revive before trying to do that. Dead people can't do things."); 
    }

    public function testCannotGiveOutRewardsWhenThereAreNone() {
        $this->character->adventureLogs->first()->update([
            'rewards' => null
        ]);

        $response = $this->actingAs($this->character->user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => 1,
             ]))->response;


        $response->assertSessionHas('error', "You cannot collect already collected rewards."); 
    }

    public function testCannotDistributeRewardsWhenAdventureing() {
        $this->character->adventureLogs->first()->update([
            'in_progress' => true,
        ]);

        $response = $this->actingAs($this->character->user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => 1,
             ]))->response;


        $response->assertSessionHas('error', "You are adventuring, you cannot do that."); 
    }

    public function testCannotDistributeRewardsWhenThereAreNoRewards() {
        $this->character->adventureLogs->first()->update([
            'rewards' => null
        ]);

        $response = $this->actingAs($this->character->user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => 1,
             ]))->response;


        $response->assertSessionHas('error', "You cannot collect already collected rewards."); 
    }
}
