<?php

namespace Tests\Unit\Game\Maps\Adventure\Jobs;

use App\Game\Maps\Adventure\Jobs\AdventureJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Adventure\Jobs\MoveTimeOutJob;
use Cache;
use Database\Seeders\GameSkillsSeeder;
use Str;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Setup\CharacterSetup;

class AdventureJobTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateAdventure;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);
    }

    public function testAdventureJob()
    {
        $user = $this->createUser();

        $adventure = $this->createNewAdventure();

        $character = (new CharacterSetup)->setupCharacter($user)
                                         ->createAdventureLog($adventure)
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

        Event::fake();

        $jobName = Str::random(80);

        Cache::put('character_'.$character->id.'_adventure_'.$adventure->id, $jobName, now()->addMinutes(5));
        
        for ($i = 1; $i <= $adventure->levels; $i++) {
            AdventureJob::dispatch($character, $adventure, $jobName, $i);

            $character->refresh();

            $this->assertTrue(!empty($character->adventureLogs->first()->logs));
        
        }
        
    }

    public function testAdventureJobDoesNotExecuteWhenNameDoesntMatch()
    {
        $user = $this->createUser();

        $adventure = $this->createNewAdventure();

        $character = (new CharacterSetup)->setupCharacter($user)
                                         ->createAdventureLog($adventure)
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

        Event::fake();

        $jobName = Str::random(80);

        Cache::put('character_'.$character->id.'_adventure_'.$adventure->id, 'sample', now()->addMinutes(5));

        for ($i = 1; $i <= $adventure->levels; $i++) {
            AdventureJob::dispatch($character, $adventure, $jobName, $i);

            $character->refresh();

            $this->assertTrue(empty($character->adventureLogs->first()->logs));
        }
    }
}
