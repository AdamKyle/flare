<?php

namespace Tests\Unit\Admin\Services;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Gem;
use App\Flare\Models\GemBagSlot;
use App\Admin\Services\AdminGemRollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminGemRollServiceTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function testRollMapGemCreatesGeneratedGemAndUpdatesCurrentPointer(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = GameMapGemParamter::factory()->create([
            'character_xp_bonus_range' => '0.1250-0.2500',
            'gold_gain_range' => '1.5000-2.5000',
            'character_power_reduction_range' => '0.0500-0.1000',
            'monster_atonement_range' => '0.3000-0.4000',
            'crafting_skill_ids' => [10, 20],
        ]);

        $gem = resolve(AdminGemRollService::class)->rollMapGem($profile, $admin);
        $profile->refresh();

        $this->assertSame(Gem::DOMAIN_MAP, $gem->domain);
        $this->assertSame($profile->name, $gem->name);
        $this->assertSame($profile->id, $gem->game_map_gem_paramters_id);
        $this->assertSame($admin->id, $gem->rolled_by_user_id);
        $this->assertSame(1, $gem->roll_number);
        $this->assertSame($gem->id, $profile->rolled_gem_id);
        $this->assertSame(1, $profile->roll_count);
        $this->assertSame([10, 20], $gem->crafting_skill_ids);
        $this->assertGreaterThanOrEqual(0.125, $gem->character_xp_bonus);
        $this->assertLessThanOrEqual(0.25, $gem->character_xp_bonus);
        $this->assertGreaterThanOrEqual(1.5, $gem->gold_gain);
        $this->assertLessThanOrEqual(2.5, $gem->gold_gain);
        $this->assertGreaterThanOrEqual(0.05, $gem->character_power_reduction);
        $this->assertLessThanOrEqual(0.1, $gem->character_power_reduction);
        $this->assertGreaterThanOrEqual(0.3, $gem->monster_atonement_amount);
        $this->assertLessThanOrEqual(0.4, $gem->monster_atonement_amount);
        $this->assertSame(0, GemBagSlot::count());
    }

    public function testRollLocationGemNeverSetsCharacterPowerReduction(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = GameLocationGemParamter::factory()->create([
            'character_xp_bonus_range' => '0.5000-0.7500',
            'monster_atonement_range' => null,
        ]);

        $gem = resolve(AdminGemRollService::class)->rollLocationGem($profile, $admin);
        $profile->refresh();

        $this->assertSame(Gem::DOMAIN_LOCATION, $gem->domain);
        $this->assertSame($profile->name, $gem->name);
        $this->assertSame($profile->id, $gem->game_location_gem_paramters_id);
        $this->assertSame($gem->id, $profile->rolled_gem_id);
        $this->assertNull($gem->character_power_reduction);
        $this->assertNull($gem->monster_atonement_amount);
        $this->assertGreaterThanOrEqual(0.5, $gem->character_xp_bonus);
        $this->assertLessThanOrEqual(0.75, $gem->character_xp_bonus);
        $this->assertSame(0, GemBagSlot::count());
    }

    public function testRerollCreatesNewGemAndKeepsPreviousGem(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = GameMapGemParamter::factory()->create();
        $service = resolve(AdminGemRollService::class);

        $firstGem = $service->rollMapGem($profile, $admin);
        $secondGem = $service->rollMapGem($profile->refresh(), $admin);
        $profile->refresh();

        $this->assertNotSame($firstGem->id, $secondGem->id);
        $this->assertNotNull(Gem::find($firstGem->id));
        $this->assertSame($secondGem->id, $profile->rolled_gem_id);
        $this->assertSame(2, $profile->roll_count);
        $this->assertSame(2, $secondGem->roll_number);
        $this->assertSame($profile->name, $firstGem->name);
        $this->assertSame($profile->name, $secondGem->name);
    }

    public function testLocationRerollKeepsProfileNameAndRollHistory(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = GameLocationGemParamter::factory()->create(['name' => 'Location Profile Name']);
        $service = resolve(AdminGemRollService::class);

        $firstGem = $service->rollLocationGem($profile, $admin);
        $secondGem = $service->rollLocationGem($profile->refresh(), $admin);
        $profile->refresh();

        $this->assertSame('Location Profile Name', $firstGem->name);
        $this->assertSame('Location Profile Name', $secondGem->name);
        $this->assertSame($profile->id, $firstGem->game_location_gem_paramters_id);
        $this->assertSame($profile->id, $secondGem->game_location_gem_paramters_id);
        $this->assertSame(1, $firstGem->roll_number);
        $this->assertSame(2, $secondGem->roll_number);
        $this->assertSame(2, $profile->roll_count);
        $this->assertSame($secondGem->id, $profile->rolled_gem_id);
    }

    public function testAllConfiguredMapRangesAreRolledIntoMatchingGemFields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $rangeMappings = [
            'character_xp_bonus_range' => 'character_xp_bonus',
            'character_class_rank_xp_bonus_range' => 'character_class_rank_xp_bonus',
            'kingdom_passive_training_reduction_range' => 'kingdom_passive_training_reduction',
            'gold_gain_range' => 'gold_gain',
            'gold_dust_gain_range' => 'gold_dust_gain',
            'shards_gain_range' => 'shards_gain',
            'copper_coin_gain_range' => 'copper_coin_gain',
            'character_class_specialty_xp_gain_range' => 'character_class_specialty_xp_gain',
            'crafting_skill_bonus_range' => 'crafting_skill_bonus',
            'item_drop_chance_increase_range' => 'item_drop_chance_increase',
            'unique_item_drop_chance_increase_range' => 'unique_item_drop_chance_increase',
            'mythic_item_drop_chance_increase_range' => 'mythic_item_drop_chance_increase',
            'cosmic_item_drop_chance_increase_range' => 'cosmic_item_drop_chance_increase',
            'ascended_item_drop_chance_increase_range' => 'ascended_item_drop_chance_increase',
            'enemy_strength_increase_range' => 'enemy_strength_increase',
            'enemy_healing_increase_range' => 'enemy_healing_increase',
            'enemy_spell_evasion_range' => 'enemy_spell_evasion',
            'enemy_affix_resistance_range' => 'enemy_affix_resistance',
            'enemy_entrancing_chance_range' => 'enemy_entrancing_chance',
            'enemy_devouring_light_chance_range' => 'enemy_devouring_light_chance',
            'enemy_devouring_darkness_chance_range' => 'enemy_devouring_darkness_chance',
            'enemy_ambush_chance_range' => 'enemy_ambush_chance',
            'enemy_ambush_resistance_range' => 'enemy_ambush_resistance',
            'enemy_counter_chance_range' => 'enemy_counter_chance',
            'enemy_counter_resistance_range' => 'enemy_counter_resistance',
            'enemy_quest_item_drop_chance_increase_range' => 'enemy_quest_item_drop_chance_increase',
            'monster_xp_increase_range' => 'monster_xp_increase',
            'monster_gold_drop_increase_range' => 'monster_gold_drop_increase',
            'faction_point_increase_range' => 'faction_point_increase',
            'character_power_reduction_range' => 'character_power_reduction',
        ];
        $profileData = array_fill_keys(array_keys($rangeMappings), '0.4321-0.4321');
        $profile = GameMapGemParamter::factory()->create($profileData);

        $gem = resolve(AdminGemRollService::class)->rollMapGem($profile, $admin);

        foreach ($rangeMappings as $gemField) {
            $this->assertSame(0.4321, $gem->{$gemField});
        }
    }

    public function testInvalidRangeFailsLoudlyWithoutCreatingGem(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = GameMapGemParamter::factory()->create([
            'character_xp_bonus_range' => 'invalid',
        ]);

        $this->expectException(InvalidArgumentException::class);

        try {
            resolve(AdminGemRollService::class)->rollMapGem($profile, $admin);
        } finally {
            $this->assertSame(0, Gem::count());
            $this->assertNull($profile->fresh()->rolled_gem_id);
        }
    }

    public function testMapReversedRangeRollsBetweenNormalizedBounds(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = GameMapGemParamter::factory()->create([
            'character_power_reduction_range' => '0.05-0.012',
        ]);

        $gem = resolve(AdminGemRollService::class)->rollMapGem($profile, $admin);

        $this->assertGreaterThanOrEqual(0.012, $gem->character_power_reduction);
        $this->assertLessThanOrEqual(0.05, $gem->character_power_reduction);
    }

    public function testLocationReversedRangeRollsBetweenNormalizedBounds(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = GameLocationGemParamter::factory()->create([
            'gold_gain_range' => '0.3-0.08',
        ]);

        $gem = resolve(AdminGemRollService::class)->rollLocationGem($profile, $admin);

        $this->assertGreaterThanOrEqual(0.08, $gem->gold_gain);
        $this->assertLessThanOrEqual(0.3, $gem->gold_gain);
    }

    public function testServiceUsesAdminNamespaceWithoutLegacyRangeImplementation(): void
    {
        $serviceSource = file_get_contents(base_path('app/Admin/Services/AdminGemRollService.php'));
        $mapRequestSource = file_get_contents(base_path('app/Admin/Requests/MapGemParamtersManagementRequest.php'));
        $locationRequestSource = file_get_contents(base_path('app/Admin/Requests/LocationGemParamtersManagementRequest.php'));

        $this->assertSame(AdminGemRollService::class, get_class(resolve(AdminGemRollService::class)));
        $this->assertFileDoesNotExist(base_path('app/Game/Gems/Services/AdminGemRollService.php'));
        $this->assertStringNotContainsString('preg_match', $serviceSource);
        $this->assertStringNotContainsString('RANGE_FIELDS', $serviceSource);
        $this->assertStringNotContainsString('RANGE_SCALE', $serviceSource);
        $this->assertStringNotContainsString('preg_match', $mapRequestSource);
        $this->assertStringNotContainsString('regex:', $mapRequestSource);
        $this->assertStringNotContainsString('preg_match', $locationRequestSource);
        $this->assertStringNotContainsString('regex:', $locationRequestSource);
    }
}
