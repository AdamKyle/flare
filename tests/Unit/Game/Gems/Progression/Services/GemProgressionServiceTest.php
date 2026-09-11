<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Game\Gems\Progression\Services\GemProgressionCurveService;
use App\Game\Gems\Progression\Services\GemProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateCharacterGameMapGemProgression;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameMapGemProgression;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateUser;

class GemProgressionServiceTest extends TestCase
{
    use CreateCharacter, CreateCharacterGameMapGemProgression, CreateGameMapGemParamter, CreateGameMapGemProgression, CreateGem, CreateUser, RefreshDatabase;

    private GemProgressionService $gemProgressionService;

    private GemProgressionCurveService $gemProgressionCurveService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gemProgressionCurveService = new GemProgressionCurveService;
        $this->gemProgressionService = new GemProgressionService($this->gemProgressionCurveService);
    }

    public function test_first_award_creates_the_global_progression_row(): void
    {
        $profile = $this->createGameMapGemParamter();

        $this->gemProgressionService->applyGlobalMapProgressionXp($profile, 500);

        $this->assertDatabaseHas('game_map_gem_progressions', [
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 1,
            'xp' => 500,
        ]);
    }

    public function test_xp_below_threshold_remains_the_same_level(): void
    {
        $profile = $this->createGameMapGemParamter();

        $result = $this->gemProgressionService->applyGlobalMapProgressionXp($profile, 500);

        $this->assertSame(1, $result->newLevel());
        $this->assertSame(500, $result->newXp());
        $this->assertFalse($result->leveledUp());
    }

    public function test_crossing_one_level_carries_the_remaining_xp(): void
    {
        $profile = $this->createGameMapGemParamter();

        $result = $this->gemProgressionService->applyGlobalMapProgressionXp($profile, 1500);

        $this->assertSame(2, $result->newLevel());
        $this->assertSame(500, $result->newXp());
        $this->assertTrue($result->leveledUp());
    }

    public function test_one_large_award_crosses_multiple_levels_correctly(): void
    {
        $profile = $this->createGameMapGemParamter();

        $costForLevelOne = $this->gemProgressionCurveService->xpRequiredForGlobalLevel(1);
        $costForLevelTwo = $this->gemProgressionCurveService->xpRequiredForGlobalLevel(2);
        $costForLevelThree = $this->gemProgressionCurveService->xpRequiredForGlobalLevel(3);

        $result = $this->gemProgressionService->applyGlobalMapProgressionXp(
            $profile,
            $costForLevelOne + $costForLevelTwo + $costForLevelThree + 123,
        );

        $this->assertSame(4, $result->newLevel());
        $this->assertSame(123, $result->newXp());
    }

    public function test_global_progression_caps_at_max_level(): void
    {
        $profile = $this->createGameMapGemParamter();
        $this->createGameMapGemProgression(['game_map_gem_paramter_id' => $profile->id, 'level' => 99, 'xp' => 0]);

        $costForLevelNinetyNine = $this->gemProgressionCurveService->xpRequiredForGlobalLevel(99);

        $result = $this->gemProgressionService->applyGlobalMapProgressionXp($profile, $costForLevelNinetyNine);

        $this->assertSame(100, $result->newLevel());
        $this->assertSame(0, $result->newXp());
        $this->assertTrue($result->isMaxLevel());

        $secondResult = $this->gemProgressionService->applyGlobalMapProgressionXp($profile, 50_000);

        $this->assertSame(100, $secondResult->newLevel());
        $this->assertSame(0, $secondResult->newXp());
    }

    public function test_personal_progression_caps_at_max_level(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 999,
            'xp' => 0,
        ]);

        $costForLevelNineNinetyNine = $this->gemProgressionCurveService->xpRequiredForPersonalLevel(999);

        $result = $this->gemProgressionService->applyPersonalMapProgressionXp($character, $profile, $costForLevelNineNinetyNine);

        $this->assertSame(1000, $result->newLevel());
        $this->assertTrue($result->isMaxLevel());
    }

    public function test_rerolling_the_active_gem_leaves_progression_unchanged(): void
    {
        $profile = $this->createGameMapGemParamter();
        $this->createGameMapGemProgression(['game_map_gem_paramter_id' => $profile->id, 'level' => 5, 'xp' => 123]);

        $newGem = $this->createMapGeneratedGem($profile);
        $profile->update(['rolled_gem_id' => $newGem->id]);

        $this->assertDatabaseHas('game_map_gem_progressions', [
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 5,
            'xp' => 123,
        ]);
    }

    public function test_character_deletion_cascades_personal_progression(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $progression = $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $character->delete();

        $this->assertDatabaseMissing('character_game_map_gem_progressions', ['id' => $progression->id]);
    }

    public function test_profile_deletion_cascades_global_and_personal_progression(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();
        $globalProgression = $this->createGameMapGemProgression(['game_map_gem_paramter_id' => $profile->id]);
        $personalProgression = $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $profile->delete();

        $this->assertDatabaseMissing('game_map_gem_progressions', ['id' => $globalProgression->id]);
        $this->assertDatabaseMissing('character_game_map_gem_progressions', ['id' => $personalProgression->id]);
    }

    public function test_sequential_locked_awards_accumulate_without_overwriting_each_other(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $profile = $this->createGameMapGemParamter();

        $this->gemProgressionService->applyPersonalMapProgressionXp($character, $profile, 200);
        $result = $this->gemProgressionService->applyPersonalMapProgressionXp($character, $profile, 300);

        $this->assertSame(500, $result->newXp());
    }
}
