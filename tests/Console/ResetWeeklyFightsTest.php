<?php

namespace Tests\Console;

use App\Flare\Models\WeeklyMonsterFight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class ResetWeeklyFightsTest extends TestCase
{
    use CreateMonster, RefreshDatabase;

    public function test_reset_removes_prior_claims_and_allows_the_new_weekly_reset(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster();
        WeeklyMonsterFight::factory()->create([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'monster_was_killed' => true,
            'reward_processed_at' => now(),
        ]);

        $this->assertSame(0, $this->artisan('reset:weekly-fights'));

        $this->assertSame(0, WeeklyMonsterFight::count());
    }
}
