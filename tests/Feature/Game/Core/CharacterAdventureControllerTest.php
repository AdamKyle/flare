<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\AdventureLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateNotification;

class CharacterAdventureControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateAdventure,
        CreateItem,
        CreateUser,
        CreateNotification;

    private $character;

    private $adventure;

    public function setUp(): void
    {
        parent::setUp();

        $this->adventure = $this->createNewAdventure();

        $item            = $this->createItem([
                               'name' => 'Spear',
                               'base_damage' => 6,
                               'type' => 'weapon',
                               'crafting_type' => 'weapon',
                           ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->updateSkill('Looting', [
                                                     'xp_towards'         => 0.10,
                                                     'level'              => 0,
                                                     'currently_training' => true,
                                                 ])
                                                 ->adventureManagement()
                                                 ->assignLog(
                                                     $this->adventure,
                                                     $item,
                                                     'Looting'
                                                 )
                                                 ->getCharacterFactory();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->adventure = null;
    }

    public function testCharacterCanSeeLatestAdventure()
    {
        $user = $this->character->getUser();

        $this->actingAs($user)
             ->visitRoute('game.current.adventure')
             ->see('Collect Rewards');
    }

    public function testCharacterCanSeeLatestAdventureWithNotification()
    {

        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $this->createNotification([
            'character_id' => $character->id,
            'title'        => 'Sample',
            'message'      => 'Sample',
            'status'       => 'success',
            'type'         => 'message',
            'read'         => false,
            'url'          => 'url',
            'adventure_id' => $character->adventureLogs()->first()->adventure_id,
        ]);

        $this->actingAs($user)
             ->visitRoute('game.current.adventure')
             ->see('Collect Rewards');
    }

    public function testCharacterCanSeeLatestAdventureWithNoRewards() {

        $user = $this->character->adventureManagement()
                                     ->updateLog(['rewards' => null])
                                     ->getCharacterFactory()
                                     ->getUser();

        $this->actingAs($user)
             ->visitRoute('game.current.adventure')
             ->dontSee('Collect Rewards');
    }

    public function testCharacterCanSeeAllAdventures() {
        $user = $this->character->getUser();

        $this->actingAs($user)
             ->visitRoute('game.completed.adventures')
             ->see('Previous Adventures')
             ->see($this->adventure->name);
    }

    public function testCharacterCanSeeAllLogsForAdventure() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $this->actingAs($user)
             ->visitRoute('game.completed.adventure', [
                 'adventureLog' => $character->adventureLogs->first()->id,
             ])
             ->see($this->adventure->name)
             ->see('Log Entry 1');
    }

    public function testCharacterCanSeeSpecificLogForAdventure() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $this->actingAs($user)
             ->visitRoute('game.completed.adventure.logs', [
                 'adventureLog' => $character->adventureLogs->first()->id,
                 'name'         => 'vcCBZhAOqy3Dg9V6a1MRWCthCGFNResjhH7ttUsFFpREdVoH9oNqyrjVny3cX8McbjyGHZYeJ8txcTov'
             ])
             ->see($this->adventure->name)
             ->see('Level: 1');
    }

    public function testCharacterCannotSeeSpecificLogForAdventureInvalidName() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $this->actingAs($user)
             ->visitRoute('game') // come here first, for a place to come back too
             ->visitRoute('game.completed.adventure.logs', [
                 'adventureLog' => $character->adventureLogs->first()->id,
                 'name'         => 'xxxx'
             ])
             ->dontSee($this->adventure->name)
             ->dontsee('Level: 1')
             ->see('Invalid input.');
    }

    public function testCharacterCannotSeeLatestAdventure()
    {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $character->adventureLogs->first()->delete();

        $this->actingAs($user)
             ->visitRoute('game') // So we have some where to redirect back too
             ->visitRoute('game.current.adventure')
             ->see('You have no currently completed adventure. Check your completed adventures for more details.');
    }

    public function testDistributeRewards() {
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => AdventureLog::first()->id,
             ]))->response;

        $response->assertSessionHas('success', [
           'You gained a level! Now level: 2',
           'Your skill: Looting gained a level and is now level: 1',
           'You gained the item: Spear',
        ]);
    }

    public function testCannotDistributeRewardsWhenDead() {
        $user = $this->character->updateCharacter(['is_dead' => true])->getUser();

        $response = $this->actingAs($user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => AdventureLog::first()->id,
             ]))->response;


        $response->assertSessionHas('error', "You are dead and must revive before trying to do that. Dead people can't do things.");
    }

    public function testShowDifferentSuccessWhenThereIsNoReward() {

        $user = $this->character->adventureManagement()->updateLog([
            'rewards' => [
                "exp" => 1,
                "gold" => 1,
                "items" => [],
                "skill" => [
                    "exp"         => 1,
                    "skill_name"  => 'Looting',
                    "exp_towards" => 10,
                ],
            ]
        ])->getCharacterFactory()->getUser();

        $response = $this->actingAs($user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => AdventureLog::first()->id,
             ]))->response;

        $response->assertSessionHas('success', ['You are a ready for your next adventure!']);
    }

    public function testCannotGiveOutRewardsWhenThereAreNone() {
        $user = $this->character->adventureManagement()->updateLog([
            'rewards' => null
        ])->getCharacterFactory()->getUser();

        $response = $this->actingAs($user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => AdventureLog::first()->id,
             ]))->response;


        $response->assertSessionHas('error', "You cannot collect already collected rewards.");
    }

    public function testCannotDistributeRewardsWhenAdventureing() {
        $user = $this->character->adventureManagement()->updateLog([
            'in_progress' => true
        ])->getCharacterFactory()->getUser();

        $response = $this->actingAs($user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => AdventureLog::first()->id,
             ]))->response;


        $response->assertSessionHas('error', "You are adventuring, you cannot do that.");
    }

    public function testCannotDistributeRewardsWhenThereAreNoRewards() {
        $user = $this->character->adventureManagement()->updateLog([
            'rewards' => null
        ])->getCharacterFactory()->getUser();

        $response = $this->actingAs($user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => AdventureLog::first()->id,
             ]))->response;


        $response->assertSessionHas('error', "You cannot collect already collected rewards.");
    }

    public function testDeleteAdventureLog() {
        $this->actingAs($this->character->getUser())->post(route('game.adventures.delete', [
            'adventureLog' => AdventureLog::first()->id
        ]));

        $this->assertTrue(AdventureLog::all()->isEmpty());
    }

    public function testBatchDeleteAdventureLog() {
        $this->actingAs($this->character->getUser())->post(route('game.adventures.batch-delete'), [
            'logs' => [AdventureLog::first()->id]
        ]);

        $this->assertTrue(AdventureLog::all()->isEmpty());
    }

    public function testCannotBatchDeleteAdventureLog() {
        $response = $this->actingAs($this->character->getUser())->post(route('game.adventures.batch-delete'), [
            'logs' => [27]
        ])->response;

        $response->assertSessionHas('error', "No logs exist for selected.");
    }
}
