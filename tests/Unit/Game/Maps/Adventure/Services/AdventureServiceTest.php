<?php

namespace Tests\Unit\Game\Maps\Adventure\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Adventure\Services\AdventureService;
use Tests\Setup\AdventureSetup;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateMonster;
use Tests\Setup\CharacterSetup;

class AdventureServiceTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateAdventure, CreateMonster;

    public function setUp(): void {
        parent::setUp();

        Queue::fake();
        Event::fake();
    }


    public function testProcessAdventureCharacterLives()
    {
        $user = $this->createUser();

        $adventure = $this->createNewAdventure();

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                         ->levelCharacterUp(10)
                                         ->createAdventureLog($adventure)
                                         ->setSkill('Accuracy', [
                                            'accuracy_bonus' => 10,
                                         ])
                                         ->setSkill('Dodge', [
                                            'dodge_bonus' => 10,
                                         ])
                                         ->setSkill('Looting', [
                                            'looting_bonus' => 10,
                                         ])
                                         ->getCharacter();


        $adventureService = new AdventureService($character, $adventure);

        $adventureService->processAdventure();
        $character->refresh();

        $this->assertFalse($character->is_dead);
        $this->assertTrue($character->adventureLogs->isNotEmpty());
        $this->assertTrue($character->adventureLogs->first()->complete);
    }

   public function testProcessAdventureCharacterDies()
   {
      $user = $this->createUser();

      $monster = $this->createMonster([
         'name' => 'Monster',
         'damage_stat' => 'str',
         'xp' => 10,
         'str' => 100,
         'dur' => 2,
         'dex' => 4,
         'chr' => 1,
         'int' => 1,
         'ac' => 1,
         'gold' => 1,
         'max_level' => 10,
         'health_range' => '999-9999',
         'attack_range' => '99-999',
         'drop_check' => 0.1,
      ]);

      $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();

      $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                       ->createAdventureLog($adventure)
                                       ->setSkill('Accuracy', [
                                          'accuracy_bonus' => 0.1,
                                       ])
                                       ->setSkill('Dodge', [
                                          'dodge_bonus' => 0.1,
                                       ])
                                       ->setSkill('Looting', [
                                          'looting_bonus' => 0.1,
                                       ])
                                       ->getCharacter();


      $adventureService = new AdventureService($character, $adventure);

      $adventureService->processAdventure();
      $character->refresh();

      $this->assertTrue($character->is_dead);
      $this->assertTrue($character->adventureLogs->isNotEmpty());
      $this->assertFalse($character->adventureLogs->first()->complete);
      $this->assertEquals(1, $character->adventureLogs->first()->last_completed_level);
   }

   public function testAppendNewLogs() {
      $user = $this->createUser();

      $monster = $this->createMonster([
         'name' => 'Monster',
         'damage_stat' => 'str',
         'xp' => 10,
         'str' => 100,
         'dur' => 2,
         'dex' => 4,
         'chr' => 1,
         'int' => 1,
         'ac' => 1,
         'gold' => 1,
         'max_level' => 10,
         'health_range' => '999-9999',
         'attack_range' => '99-999',
         'drop_check' => 0.1,
      ]);

      $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();

      $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                       ->levelCharacterUp(10)
                                       ->createAdventureLog($adventure, [
                                          'logs' => [['adventure' => 'sample']],
                                       ])
                                       ->setSkill('Accuracy', [
                                          'accuracy_bonus' => 10,
                                       ])
                                       ->setSkill('Dodge', [
                                          'dodge_bonus' => 10,
                                       ])
                                       ->setSkill('Looting', [
                                          'looting_bonus' => 10,
                                       ])
                                       ->getCharacter();


      $adventureService = new AdventureService($character, $adventure);

      $adventureService->processAdventure();
      $character->refresh();

      $logs = $character->adventureLogs->first()->logs;
      
      // We start with one, for this test:
      $this->assertTrue(count($logs) > 1);
   }

   public function testStartAdventureWithExistingLevelCompletedAt() {
      $user = $this->createUser();

      $monster = $this->createMonster([
         'name' => 'Monster',
         'damage_stat' => 'str',
         'xp' => 10,
         'str' => 100,
         'dur' => 2,
         'dex' => 4,
         'chr' => 1,
         'int' => 1,
         'ac' => 1,
         'gold' => 1,
         'max_level' => 10,
         'health_range' => '999-9999',
         'attack_range' => '99-999',
         'drop_check' => 0.1,
      ]);

      $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();

      $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                       ->levelCharacterUp(10)
                                       ->createAdventureLog($adventure, [
                                          'last_completed_level' => 1
                                       ])
                                       ->setSkill('Accuracy', [
                                          'accuracy_bonus' => 10,
                                       ])
                                       ->setSkill('Dodge', [
                                          'dodge_bonus' => 10,
                                       ])
                                       ->setSkill('Looting', [
                                          'looting_bonus' => 10,
                                       ])
                                       ->getCharacter();


      $adventureService = new AdventureService($character, $adventure);

      $adventureService->processAdventure();
      $character->refresh();

      $logs = $character->adventureLogs->first()->logs;
      
      // We start with one, for this test:
      $this->assertTrue(!empty($logs));
   }
}
