<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\AdventureLog;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Setup\CharacterSetup;
use Tests\Traits\CreateUser;

class AdventureLogTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateUser;


    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $adventure = $this->createNewAdventure();

        (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                            ->levelCharacterUp(10)
                            ->createAdventureLog($adventure, [
                                'complete'             => true,
                                'in_progress'          => false,
                                'last_completed_level' => 1,
                            ])
                            ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                'xp_towards' => 10,
                            ], true)
                            ->setSkill('Dodge', [
                                'skill_bonus_per_level' => 10,
                            ])
                            ->setSkill('Looting', [
                                'skill_bonus_per_level' => 0,
                             ])
                            ->getCharacter();
    }

    public function testCanGetCharacterForAdventure() {
        $this->assertNotNull(AdventureLog::first()->character);
    }
}
