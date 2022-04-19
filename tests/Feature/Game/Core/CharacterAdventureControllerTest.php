<?php

namespace Tests\Feature\Game\Core;

use App\Game\Adventures\View\AdventureCompletedRewards;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Flare\Models\AdventureLog;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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



        $item            = $this->createItem([
                               'name' => 'Spear',
                               'base_damage' => 6,
                               'type' => 'weapon',
                               'crafting_type' => 'weapon',
                           ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->assignFactionSystem()
                                                 ->updateSkill('Looting', [
                                                     'xp_towards'         => 0.10,
                                                     'level'              => 0,
                                                     'currently_training' => true,
                                                 ]);

        $this->adventure = $this->createNewAdventure();

        $this->character = $this->character
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

    public function testCharacterCanSeeLatestAdventureWithCache()
    {

        Cache::put('current-adventure-' . $this->character->getCharacter(false)->id, [
            'adventure_id' => $this->adventure->id
        ]);

        $user = $this->character->getUser();

        $this->actingAs($user)
            ->visitRoute('game.current.adventure')
            ->see('Collect Rewards');
    }

    public function testCharacterCanSeeLatestAdventureWithNotification()
    {

        $user      = $this->character->getUser();
        $character = $this->character->getCharacter(false);

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
                                     ->updateLog(['rewards' =>
                                         [
                                             "Level 1" =>[
                                                 "Goblin-VhaXIEyO7c" => [
                                                     "exp" =>3,
                                                     "gold" =>25,
                                                     "faction_points" => 25,
                                                     "items" =>[
                                                     ],
                                                 ]
                                             ],
                                         ]
                                     ])
                                     ->getCharacterFactory()
                                     ->getUser();

        $this->actingAs($user)
             ->visitRoute('game.current.adventure')
             ->see('Collect Rewards');
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
        $character = $this->character->getCharacter(false);

        $this->actingAs($user)
             ->visitRoute('game.completed.adventure', [
                 'adventureLog' => $character->adventureLogs->first()->id,
             ])
             ->see($this->adventure->name)
             ->see('Level 1');
    }

    public function testCharacterCannotSeeLatestAdventure()
    {
        $character = $this->character->getCharacter(false);
        $user      = $this->character->getUser();

        $character->adventureLogs->first()->delete();

        $this->actingAs($user)
             ->visitRoute('game') // So we have somewhere to redirect back too
             ->visitRoute('game.current.adventure')
             ->see('You currently have no completed adventure. Check your completed adventures for more details.');
    }

    public function testCharacterCannotSeeLatestAdventureWithNoAdventureId()
    {
        $character = $this->character->getCharacter(false);
        $user      = $this->character->getUser();

        $character->update([
            'current_adventure_id' => null,
        ]);

        $this->actingAs($user)
            ->visitRoute('game') // So we have somewhere to redirect back too
            ->visitRoute('game.current.adventure')
            ->see('You currently have no completed adventure. Check your completed adventures for more details.');
    }

    public function testDistributeRewards() {
        Queue::fake();

        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
             ->post(route('game.current.adventure.reward', [
                 'adventureLog' => AdventureLog::first()->id,
             ]))->response;

        $this->assertEquals(302, $response->status());
    }

    public function testDistributeRewardsWithMultipleLevelsGained() {
        Queue::fake();

        $item = $this->createItem([
            'type' => 'quest'
        ]);

        $user      = $this->character->adventureManagement()->updateLog([
            'rewards' =>
                [
                    "Level 1" =>[
                        "Goblin-VhaXIEyO7c" => [
                            "exp" => 700,
                            "gold" => 25,
                            "faction_points" => 1,
                            "items" =>[
                                [
                                    "id" =>$item->id,
                                    "name" =>$item->affix_name
                                ]
                            ],
                            "skill" =>[
                                "exp" => 700,
                                "skill_name" =>"Looting",
                                "exp_towards" =>0.1
                            ]
                        ]
                    ],
                ]
        ])->getCharacterFactory()->getUser();

        $response = $this->actingAs($user)
                         ->post(route('game.current.adventure.reward', [
                             'adventureLog' => AdventureLog::first()->id,
                         ]))->response;

        $this->assertEquals(302, $response->status());
    }

    public function testDistributeRewardsWhenInventoryIsFull() {
        $user      = $this->character->updateCharacter([
            'inventory_max' => 0,
        ])->getUser();

        $response = $this->actingAs($user)
            ->post(route('game.current.adventure.reward', [
                'adventureLog' => AdventureLog::first()->id,
            ]))->response;

        $response->assertSessionDoesntHaveErrors();
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
        Queue::fake();

        $user = $this->character->adventureManagement()->updateLog([
            'rewards' => [
                "Level 1" =>
                    [
                        "Goblin-N0Km0lwpDy" => [
                            "exp" => 3,
                            "gold" => 25,
                            "faction_points" => 1,
                            "items" => [],
                        ]
                    ]
            ]
        ])->getCharacterFactory()->getUser();

        $response = $this->actingAs($user)
                         ->post(route('game.current.adventure.reward', [
                             'adventureLog' => AdventureLog::first()->id,
                         ]))->response;

        $this->assertEquals(302, $response->status());

        $response->assertSessionHas('success');
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

    public function testCannotDistributeRewardsWhenAdventuring() {
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

    public function testCannotDeleteAdventureLogWhenAdventureInProgress() {

        $this->character->adventureManagement()->updateLog([
            'in_progress' => true,
        ]);

        $character = $this->character->getCharacter(false);

        $this->actingAs($character->user)->post(route('game.adventures.delete', [
            'adventureLog' => AdventureLog::first()->id
        ]));

        $this->assertFalse(AdventureLog::all()->isEmpty());
    }

    public function testBatchDeleteAdventureLog() {
        $this->actingAs($this->character->getUser())->post(route('game.adventures.batch-delete'), [
            'logs' => [AdventureLog::first()->id]
        ]);

        $this->assertTrue(AdventureLog::all()->isEmpty());
    }

    public function testCannotBatchDeleteAdventureLog() {
        $response = $this->actingAs($this->character->getUser())->visit(route('game.completed.adventures'))->post(route('game.adventures.batch-delete'), [
            'logs' => [27]
        ])->response;

        $response->assertRedirectedToRoute('game.completed.adventures');
    }
}
