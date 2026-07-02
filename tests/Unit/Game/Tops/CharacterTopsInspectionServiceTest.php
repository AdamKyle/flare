<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Game\Tops\Services\CharacterTopsInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterTopsInspectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testOverviewReturnsPublicCharacterFields(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Public']);

        $data = $this->app->make(CharacterTopsInspectionService::class)->overview($character);

        $this->assertSame('Public', $data['name']);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function testStatsReturnsExplicitStatGroups(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->stats($character);

        $this->assertArrayHasKey('base_stats', $data);
        $this->assertArrayHasKey('modded_stats', $data);
        $this->assertArrayHasKey('resistances', $data);
        $this->assertArrayHasKey('stat_breakdown', $data);
    }

    public function testEquipmentReturnsExplicitEquipmentRows(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->equipment($character);

        $this->assertArrayHasKey('source', $data);
        $this->assertArrayHasKey('set_name', $data);
        $this->assertArrayHasKey('items', $data);
    }

    public function testSkillsReturnsExplicitSkillGroups(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->skills($character);

        $this->assertArrayHasKey('regular_skills', $data);
        $this->assertArrayHasKey('passive_skills', $data);
        $this->assertArrayHasKey('class_ranks', $data);
        $this->assertArrayHasKey('class_specialties_equipped', $data);
    }

    public function testFactionsReturnsExplicitFactionGroups(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->factions($character);

        $this->assertArrayHasKey('factions', $data);
        $this->assertArrayHasKey('loyalties', $data);
        $this->assertArrayHasKey('npcs', $data);
        $this->assertArrayHasKey('automation_summary', $data);
    }

    public function testActivityReturnsExplicitActivityGroups(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $data = $this->app->make(CharacterTopsInspectionService::class)->activity($character);

        $this->assertArrayHasKey('login_duration_7_days', $data);
        $this->assertArrayHasKey('exploration_run_count', $data);
        $this->assertArrayHasKey('delve_outcome_counts', $data);
        $this->assertArrayHasKey('quest_completion_count', $data);
    }
}
