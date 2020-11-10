<?php

namespace Tests\Unit\Game\Maps\Adventure\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Adventure\Services\AdventureService;
use App\Game\Maps\Adventure\Builders\RewardBuilder;
use Database\Seeders\GameSkillsSeeder;
use Tests\Setup\AdventureSetup;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateMonster;
use Tests\Setup\CharacterSetup;

class AdventureServiceTest extends TestCase
{
    use RefreshDatabase, 
        CreateUser, 
        CreateAdventure, 
        CreateMonster;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);

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
                                            'skill_bonus_per_level' => 10,
                                        ], [
                                            'xp_towards' => 10,
                                        ], true)
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
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
        $user = $this->createUser();

        $adventure = $this->createNewAdventure(null, 5);

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->levelCharacterUp(10)
                                        ->createAdventureLog($adventure)
                                        ->setSkill('Accuracy', [
                                            'skill_bonus_per_level' => 10,
                                        ], [
                                            'xp_towards' => 10,
                                        ], true)
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 10,
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

    public function testProcessAdventureCharacterDies()
    {
        $user = $this->createUser();

        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 500,
            'dur' => 2,
            'dex' => 4,
            'chr' => 1,
            'int' => 1,
            'ac' => 100,
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
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();


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
}
