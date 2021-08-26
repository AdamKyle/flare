<?php

namespace Tests\Unit\Game\Maps\Adventure\Services;

use App\Flare\Calculators\DropCheckCalculator;
use App\Flare\Calculators\GoldRushCheckCalculator;
use DB;
use Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Game\Adventures\Services\AdventureService;
use App\Game\Adventures\Builders\RewardBuilder;
use App\Game\Adventures\Mail\AdventureCompleted;
use Mockery;
use Tests\Setup\AdventureSetup;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;

class AdventureServiceTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateAdventure,
        CreateMonster,
        CreateGameSkill,
        CreateItemAffix,
        CreateItem;

    public function setUp(): void {
        parent::setUp();

        $this->createItemAffix();

        Queue::fake();
        Event::fake();
    }

    public function testProcessAdventureCharacterLives()
    {
        $adventure = $this->createNewAdventure();
        $item      = $this->createItem(['name' => 'Item Name']);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
                                        ->updateCharacter(['can_move' => false])
                                        ->levelCharacterUp(100)
                                        ->inventoryManagement()
                                        ->giveItem($item)
                                        ->getCharacterFactory()
                                        ->createAdventureLog($adventure)
                                        ->updateSkill('Accuracy', [
                                            'level' => 10,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->updateSkill('Dodge', [
                                            'level' => 10
                                        ])
                                        ->updateSkill('Looting', [
                                            'level' => 10
                                        ])
                                        ->getCharacter();

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

            $adventureService->processAdventure($i, $adventure->levels);
            $character->refresh();

            $this->assertFalse($character->is_dead);
            $this->assertTrue($character->adventureLogs->isNotEmpty());
            $this->assertTrue($character->adventureLogs->first()->complete);
            $this->assertTrue(!empty($character->adventureLogs->first()->rewards));
            $this->assertTrue(!empty($character->adventureLogs->first()->logs));
        }

    }

    public function testProcessAdventureWithMultipleLevels()
    {
        $adventure = $this->createNewAdventure(null, 5);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
                                        ->levelCharacterUp(10)
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->updateSkill('Accuracy', [
                                            'level' => 10,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->updateSkill('Dodge', [
                                            'level' => 10
                                        ])
                                        ->updateSkill('Looting', [
                                            'level' => 10
                                        ])
                                        ->getCharacter();

         $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService->processAdventure($i, $adventure->levels);
        }

        $character = $character->refresh();

        $this->assertEquals(5, $character->adventureLogs->first()->last_completed_level);

        foreach($character->adventureLogs->first()->logs as $key => $value) {
            $this->assertEquals(5, count($value));
        }
    }

    public function testProcessAdventureWithMultipleLevelsAndGetQuestItem()
    {
        $item = $this->createItem([
            'name' => 'Apples',
            'type' => 'quest',
        ]);

        $monster = $this->createMonster([
            'quest_item_id'          => $item->id,
            'quest_item_drop_chance' => 0.05,
        ]);

        $adventure = $this->createNewAdventure($monster, 5);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->updateCharacter(['can_move' => false])
            ->createAdventureLog($adventure)
            ->updateSkill('Accuracy', [
                'level' => 10,
                'xp_towards' => 10,
                'currently_training' => true
            ])
            ->updateSkill('Dodge', [
                'level' => 10
            ])
            ->updateSkill('Looting', [
                'level' => 10
            ])
            ->getCharacter();

        $dropCheckCalculator = Mockery::mock(DropCheckCalculator::class)->makePartial();

        $this->app->instance(DropCheckCalculator::class, $dropCheckCalculator);

        $dropCheckCalculator->shouldReceive('fetchDropCheckChance')->andReturn(true);

        $dropCheckCalculator->shouldReceive('fetchQuestItemDropCheck')->andReturn(true);

        $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService->processAdventure($i, $adventure->levels);
        }

        $this->assertTrue(true);

        $character = $character->refresh();

        $this->assertEquals(5, $character->adventureLogs->first()->last_completed_level);

        foreach($character->adventureLogs->first()->logs as $key => $value) {
            $this->assertEquals(5, count($value));
        }

        foreach ($character->adventureLogs->first()->rewards as $key => $value) {
            if ($key === 'items') {
                $this->assertNotFalse(array_search($item->id, array_column($value, 'id')));
            }
        }
    }

    public function testProcessAdventureWithMultipleLevelsAndCannotReceiveQuestItem()
    {
        $item = $this->createItem([
            'name' => 'Apples',
            'type' => 'quest',
        ]);

        $monster = $this->createMonster([
            'quest_item_id'          => $item->id,
            'quest_item_drop_chance' => 0.05,
        ]);

        $adventure = $this->createNewAdventure($monster, 5);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->updateCharacter(['can_move' => false])
            ->createAdventureLog($adventure)
            ->updateSkill('Accuracy', [
                'level' => 10,
                'xp_towards' => 10,
                'currently_training' => true
            ])
            ->updateSkill('Dodge', [
                'level' => 10
            ])
            ->updateSkill('Looting', [
                'level' => 10
            ])
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $dropCheckCalculator = Mockery::mock(DropCheckCalculator::class)->makePartial();

        $this->app->instance(DropCheckCalculator::class, $dropCheckCalculator);

        $dropCheckCalculator->shouldReceive('fetchDropCheckChance')->andReturn(true);

        $dropCheckCalculator->shouldReceive('fetchQuestItemDropCheck')->andReturn(true);

        $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService->processAdventure($i, $adventure->levels);
        }

        $character = $character->refresh();

        $this->assertEquals(5, $character->adventureLogs->first()->last_completed_level);

        foreach($character->adventureLogs->first()->logs as $key => $value) {
            $this->assertEquals(5, count($value));
        }

        foreach ($character->adventureLogs->first()->rewards as $key => $value) {
            if ($key === 'items') {
                $this->assertFalse(array_search($item->id, array_column($value, 'id')));
            }
        }
    }

    public function testProcessAdventureWithMultipleLevelsWithNoDrops()
    {
        $adventure = $this->createNewAdventure(null, 5);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
                                        ->levelCharacterUp(10)
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->updateSkill('Accuracy', [
                                            'level' => 10,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->getCharacter();

        $dropCheckCalculator = Mockery::mock(DropCheckCalculator::class)->makePartial();

        $this->app->instance(DropCheckCalculator::class, $dropCheckCalculator);

        $dropCheckCalculator->shouldReceive('fetchDropCheckChance')->andReturn(false);

        $goldRushChange = Mockery::mock(GoldRushCheckCalculator::class)->makePartial();

        $this->app->instance(GoldRushCheckCalculator::class, $goldRushChange);

        $goldRushChange->shouldReceive('fetchGoldRushChance')->andReturn(false);

        $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService->processAdventure($i, $adventure->levels);
        }

        $character = $character->refresh();

        $this->assertEquals(5, $character->adventureLogs->first()->last_completed_level);

        foreach($character->adventureLogs->first()->logs as $key => $value) {
            $this->assertEquals(5, count($value));
        }
    }

    public function testProcessAdventureWithMultipleLevelsNotTrainingSkills()
    {
        $adventure = $this->createNewAdventure(null, 5);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
                                        ->levelCharacterUp(10)
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->updateSkill('Accuracy', [
                                            'level' => 10,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->updateSkill('Dodge', [
                                            'level' => 10
                                        ])
                                        ->updateSkill('Looting', [
                                            'level' => 10
                                        ])
                                        ->getCharacter();

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

            $adventureService->processAdventure($i, $adventure->levels);
        }

        $character = $character->refresh();

        $this->assertEquals(5, $character->adventureLogs->first()->last_completed_level);

        foreach($character->adventureLogs->first()->logs as $key => $value) {
            $this->assertEquals(5, count($value));
        }
    }

    public function testProcessAdventureCharacterDiesLoggedIn()
    {
        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 500,
            'dur' => 500,
            'dex' => 500,
            'chr' => 500,
            'int' => 500,
            'ac' => 500,
            'gold' => 1,
            'max_level' => 500,
            'health_range' => '999-9999',
            'attack_range' => '99-999',
            'drop_check' => 0.1,
        ]);

        $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->updateSkill('Accuracy', [
                                            'level' => 0,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->updateSkill('Dodge', [
                                            'level' => 0
                                        ])
                                        ->updateSkill('Looting', [
                                            'level' => 0
                                        ])
                                        ->getCharacter();

        $this->actingAs($character->user);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

            $adventureService->processAdventure($i, $adventure->levels);
        }

        $character = $character->refresh();

        $this->assertTrue($character->is_dead);
        $this->assertTrue($character->adventureLogs->isNotEmpty());
        $this->assertFalse($character->adventureLogs->first()->complete);
        $this->assertEquals(1, $character->adventureLogs->first()->last_completed_level);
    }


    public function testProcessAdventureCharacterDiesNotLoggedIn()
    {
        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 500,
            'dur' => 500,
            'dex' => 500,
            'chr' => 500,
            'int' => 500,
            'ac' => 500,
            'gold' => 1,
            'max_level' => 10,
            'health_range' => '999-9999',
            'attack_range' => '99-999',
            'drop_check' => 0.1,
        ]);

        $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->updateSkill('Accuracy', [
                                            'level' => 0,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->updateSkill('Dodge', [
                                            'level' => 0
                                        ])
                                        ->updateSkill('Looting', [
                                            'level' => 0
                                        ])
                                        ->getCharacter();

        Mail::fake();

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

            $adventureService->processAdventure($i, $adventure->levels);
        }

        $character = $character->refresh();

        Mail::assertSent(AdventureCompleted::class, 1);

        $this->assertTrue($character->is_dead);
        $this->assertTrue($character->adventureLogs->isNotEmpty());
        $this->assertFalse($character->adventureLogs->first()->complete);
        $this->assertEquals(1, $character->adventureLogs->first()->last_completed_level);
    }

    public function testAdventureTookTooLongUserOnline() {
        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 1,
            'dur' => 12,
            'dex' => 23, // This is the same as the character, to make the aadventure take too long.
            'chr' => 12,
            'int' => 10,
            'ac' => 18,
            'gold' => 1,
            'max_level' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-4',
            'drop_check' => 0.1,
        ]);

        $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();


        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
                                        ->levelCharacterUp(10)
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->getCharacter();

        $this->actingAs($character->user);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

            $adventureService->processAdventure($i, $adventure->levels);
        }

        $character = $character->refresh();

        $this->assertTrue($character->can_move);
    }

    public function testAdventureTookTooLongUserNotOnline() {
        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 1,
            'dur' => 12,
            'dex' => 23, // To match the character dex so the adventure takes too long.
            'chr' => 12,
            'int' => 10,
            'ac' => 18,
            'gold' => 1,
            'max_level' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-4',
            'drop_check' => 0.1,
        ]);

        $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();


        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
                                        ->levelCharacterUp(10)
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->getCharacter();

        Mail::fake();

        for ($i = 1; $i <= $adventure->levels; $i++) {
            $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

            $adventureService->processAdventure($i, $adventure->levels);
        }

        $character = $character->refresh();

        $this->assertTrue($character->can_move);

        Mail::assertSent(AdventureCompleted::class);
    }
}
