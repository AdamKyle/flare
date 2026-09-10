<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\AdminGemRollService;
use App\Flare\Models\Gem;
use App\Flare\Models\GemBagSlot;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminGemRollServiceTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMapGemParamter, CreateRole, CreateUser, RefreshDatabase;

    public function test_roll_map_gem_creates_generated_gem_and_updates_current_pointer(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter([
            'character_xp_bonus_range' => '0.1250-0.2500',
            'gold_gain_range' => '1.5000-2.5000',
            'character_power_reduction_range' => '0.0500-0.1000',
            'monster_atonement_range' => '0.3000-0.4000',
            'crafting_skill_ids' => [10, 20],
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollMapGem($profile, $admin);
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

    public function test_roll_map_gem_with_no_admin_persists_a_system_roll(): void
    {
        $profile = $this->createGameMapGemParamter([
            'character_xp_bonus_range' => '0.4321-0.4321',
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollMapGem($profile, null);
        $profile->refresh();

        $this->assertSame(Gem::DOMAIN_MAP, $gem->domain);
        $this->assertNull($gem->rolled_by_user_id);
        $this->assertSame($gem->id, $profile->rolled_gem_id);
        $this->assertSame(1, $profile->roll_count);
    }

    public function test_roll_location_gem_with_no_admin_persists_a_system_roll(): void
    {
        $profile = $this->createGameLocationGemParamter([
            'character_xp_bonus_range' => '0.4321-0.4321',
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollLocationGem($profile, null);
        $profile->refresh();

        $this->assertSame(Gem::DOMAIN_LOCATION, $gem->domain);
        $this->assertNull($gem->rolled_by_user_id);
        $this->assertSame($gem->id, $profile->rolled_gem_id);
        $this->assertSame(1, $profile->roll_count);
    }

    public function test_roll_location_gem_never_sets_character_power_reduction(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter([
            'character_xp_bonus_range' => '0.5000-0.7500',
            'monster_atonement_range' => null,
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollLocationGem($profile, $admin);
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

    public function test_reroll_creates_new_gem_and_keeps_previous_gem(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter();
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $service = new AdminGemRollService($randomNumberGenerator);

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

    public function test_location_reroll_keeps_profile_name_and_roll_history(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Location Profile Name']);
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $service = new AdminGemRollService($randomNumberGenerator);

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

    public function test_all_configured_map_ranges_are_rolled_into_matching_gem_fields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter([
            'character_xp_bonus_range' => '0.4321-0.4321',
            'character_class_rank_xp_bonus_range' => '0.4321-0.4321',
            'kingdom_passive_training_reduction_range' => '0.4321-0.4321',
            'gold_gain_range' => '0.4321-0.4321',
            'gold_dust_gain_range' => '0.4321-0.4321',
            'shards_gain_range' => '0.4321-0.4321',
            'copper_coin_gain_range' => '0.4321-0.4321',
            'character_class_specialty_xp_gain_range' => '0.4321-0.4321',
            'crafting_skill_bonus_range' => '0.4321-0.4321',
            'item_drop_chance_increase_range' => '0.4321-0.4321',
            'unique_item_drop_chance_increase_range' => '0.4321-0.4321',
            'mythic_item_drop_chance_increase_range' => '0.4321-0.4321',
            'cosmic_item_drop_chance_increase_range' => '0.4321-0.4321',
            'enemy_strength_increase_range' => '0.4321-0.4321',
            'enemy_healing_increase_range' => '0.4321-0.4321',
            'enemy_spell_evasion_range' => '0.4321-0.4321',
            'enemy_affix_resistance_range' => '0.4321-0.4321',
            'enemy_entrancing_chance_range' => '0.4321-0.4321',
            'enemy_devouring_light_chance_range' => '0.4321-0.4321',
            'enemy_devouring_darkness_chance_range' => '0.4321-0.4321',
            'enemy_ambush_chance_range' => '0.4321-0.4321',
            'enemy_ambush_resistance_range' => '0.4321-0.4321',
            'enemy_counter_chance_range' => '0.4321-0.4321',
            'enemy_counter_resistance_range' => '0.4321-0.4321',
            'enemy_quest_item_drop_chance_increase_range' => '0.4321-0.4321',
            'monster_xp_increase_range' => '0.4321-0.4321',
            'monster_gold_drop_increase_range' => '0.4321-0.4321',
            'character_power_reduction_range' => '0.4321-0.4321',
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollMapGem($profile, $admin);

        $this->assertSame(0.4321, $gem->character_xp_bonus);
        $this->assertSame(0.4321, $gem->character_class_rank_xp_bonus);
        $this->assertSame(0.4321, $gem->kingdom_passive_training_reduction);
        $this->assertSame(0.4321, $gem->gold_gain);
        $this->assertSame(0.4321, $gem->gold_dust_gain);
        $this->assertSame(0.4321, $gem->shards_gain);
        $this->assertSame(0.4321, $gem->copper_coin_gain);
        $this->assertSame(0.4321, $gem->character_class_specialty_xp_gain);
        $this->assertSame(0.4321, $gem->crafting_skill_bonus);
        $this->assertSame(0.4321, $gem->item_drop_chance_increase);
        $this->assertSame(0.4321, $gem->unique_item_drop_chance_increase);
        $this->assertSame(0.4321, $gem->mythic_item_drop_chance_increase);
        $this->assertSame(0.4321, $gem->cosmic_item_drop_chance_increase);
        $this->assertSame(0.4321, $gem->enemy_strength_increase);
        $this->assertSame(0.4321, $gem->enemy_healing_increase);
        $this->assertSame(0.4321, $gem->enemy_spell_evasion);
        $this->assertSame(0.4321, $gem->enemy_affix_resistance);
        $this->assertSame(0.4321, $gem->enemy_entrancing_chance);
        $this->assertSame(0.4321, $gem->enemy_devouring_light_chance);
        $this->assertSame(0.4321, $gem->enemy_devouring_darkness_chance);
        $this->assertSame(0.4321, $gem->enemy_ambush_chance);
        $this->assertSame(0.4321, $gem->enemy_ambush_resistance);
        $this->assertSame(0.4321, $gem->enemy_counter_chance);
        $this->assertSame(0.4321, $gem->enemy_counter_resistance);
        $this->assertSame(0.4321, $gem->enemy_quest_item_drop_chance_increase);
        $this->assertSame(0.4321, $gem->monster_xp_increase);
        $this->assertSame(0.4321, $gem->monster_gold_drop_increase);
        $this->assertSame(0.4321, $gem->character_power_reduction);
    }

    public function test_invalid_range_fails_loudly_without_creating_gem(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter([
            'character_xp_bonus_range' => 'invalid',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        (new AdminGemRollService($randomNumberGenerator))->rollMapGem($profile, $admin);
    }

    public function test_map_reversed_range_rolls_between_normalized_bounds(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter([
            'character_power_reduction_range' => '0.05-0.012',
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollMapGem($profile, $admin);

        $this->assertGreaterThanOrEqual(0.012, $gem->character_power_reduction);
        $this->assertLessThanOrEqual(0.05, $gem->character_power_reduction);
    }

    public function test_location_reversed_range_rolls_between_normalized_bounds(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter([
            'gold_gain_range' => '0.3-0.08',
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollLocationGem($profile, $admin);

        $this->assertGreaterThanOrEqual(0.08, $gem->gold_gain);
        $this->assertLessThanOrEqual(0.3, $gem->gold_gain);
    }

    public function test_map_stored_zero_range_is_absent(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter([
            'character_power_reduction_range' => '0',
            'gold_gain_range' => '0.4321-0.4321',
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollMapGem($profile, $admin);
        $profile->refresh();

        $this->assertSame($gem->id, $profile->rolled_gem_id);
        $this->assertSame(1, $profile->roll_count);
        $this->assertNull($gem->character_power_reduction);
        $this->assertSame(0.4321, $gem->gold_gain);
    }

    public function test_location_stored_zero_range_is_absent(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter([
            'gold_gain_range' => '0',
            'character_xp_bonus_range' => '0.4321-0.4321',
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollLocationGem($profile, $admin);
        $profile->refresh();

        $this->assertSame($gem->id, $profile->rolled_gem_id);
        $this->assertSame(1, $profile->roll_count);
        $this->assertNull($gem->gold_gain);
        $this->assertSame(0.4321, $gem->character_xp_bonus);
    }

    public function test_zero_only_range_is_absent(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter([
            'character_power_reduction_range' => '0-0',
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        $gem = (new AdminGemRollService($randomNumberGenerator))->rollMapGem($profile, $admin);

        $this->assertNull($gem->character_power_reduction);
    }

    public function test_malformed_nonzero_scalar_range_still_fails(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter([
            'character_xp_bonus_range' => '5',
        ]);

        $this->assertSame(0, $profile->roll_count);
        $this->assertNull($profile->rolled_gem_id);
        $this->assertSame(0, Gem::count());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid gem roll range: 5');

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->zeroOrMoreTimes()->andReturn(500000);
        (new AdminGemRollService($randomNumberGenerator))->rollMapGem($profile, $admin);
    }
}
