<?php

namespace Tests\Unit\Game\Maps\Adventure\Services;

use DB;
use Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Game\Adventures\Services\AdventureService;
use App\Game\Adventures\Builders\RewardBuilder;
use App\Game\Adventures\Mail\AdventureCompleted;
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

        $item = $this->createItem(['name' => 'Item Name']);

        $character = (new CharacterFactory)->createBaseCharacter()
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

    public function testProcessAdventureWithMultipleLevelsNotTrainingSkills()
    {
        $adventure = $this->createNewAdventure(null, 5);

        $character = (new CharacterFactory)->createBaseCharacter()
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
            'dex' => 13,
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
                                        ->levelCharacterUp(10)
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->updateSkill('Accuracy', [
                                            'level' => 100,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->updateSkill('Dodge', [
                                            'level' => 100
                                        ])
                                        ->updateSkill('Looting', [
                                            'level' => 100
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

        $this->assertTrue($character->can_move);
    }

    public function testAdventureTookTooLongUserNotOnline() {
        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 1,
            'dur' => 12,
            'dex' => 13,
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
                                        ->levelCharacterUp(10)
                                        ->updateCharacter(['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->updateSkill('Accuracy', [
                                            'level' => 10,
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->updateSkill('Dodge', [
                                            'level' => 100
                                        ])
                                        ->updateSkill('Looting', [
                                            'level' => 10
                                        ])
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
