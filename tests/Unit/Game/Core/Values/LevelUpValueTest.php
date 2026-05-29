<?php

namespace Tests\Unit\Game\Core\Values;

use App\Game\Core\Values\LevelUpValue;
use App\Game\Reincarnate\Values\MaxReincarnationStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class LevelUpValueTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_level_up_without_boon_increases_level_and_stats_by_one_level(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character);

        $this->assertSame(2, $levelUpValue['level']);
        $this->assertSame(2, $levelUpValue['str']);
        $this->assertSame(2, $levelUpValue['dur']);
        $this->assertSame(3, $levelUpValue['dex']);
        $this->assertSame(2, $levelUpValue['chr']);
        $this->assertSame(2, $levelUpValue['int']);
        $this->assertSame(2, $levelUpValue['agi']);
        $this->assertSame(2, $levelUpValue['focus']);
    }

    public function test_stackable_extra_level_boon_increases_stats_by_actual_levels_gained(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Stackable Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(6, $levelUpValue['level']);
        $this->assertSame(6, $levelUpValue['str']);
        $this->assertSame(6, $levelUpValue['dur']);
        $this->assertSame(11, $levelUpValue['dex']);
        $this->assertSame(6, $levelUpValue['chr']);
        $this->assertSame(6, $levelUpValue['int']);
        $this->assertSame(6, $levelUpValue['agi']);
        $this->assertSame(6, $levelUpValue['focus']);
    }

    public function test_extra_level_boon_near_max_level_scales_stats_only_by_actual_levels_gained_after_clamp(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Near Max Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->update([
            'level' => 999,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(1000, $levelUpValue['level']);
        $this->assertSame(2, $levelUpValue['str']);
        $this->assertSame(2, $levelUpValue['dur']);
        $this->assertSame(3, $levelUpValue['dex']);
        $this->assertSame(2, $levelUpValue['chr']);
        $this->assertSame(2, $levelUpValue['int']);
        $this->assertSame(2, $levelUpValue['agi']);
        $this->assertSame(2, $levelUpValue['focus']);
    }

    public function test_multiple_active_stackable_extra_level_boons_sum_raw_stat_gains(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $firstBoon = $this->createItem([
            'name' => 'First Stackable Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $secondBoon = $this->createItem([
            'name' => 'Second Stackable Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $firstBoon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 2,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $secondBoon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 3,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(7, $levelUpValue['level']);
        $this->assertSame(7, $levelUpValue['str']);
        $this->assertSame(7, $levelUpValue['dur']);
        $this->assertSame(13, $levelUpValue['dex']);
        $this->assertSame(7, $levelUpValue['chr']);
        $this->assertSame(7, $levelUpValue['int']);
        $this->assertSame(7, $levelUpValue['agi']);
        $this->assertSame(7, $levelUpValue['focus']);
    }

    public function test_extra_level_boon_with_zero_amount_used_does_not_break_raw_stat_gains(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $zeroAmountBoon = $this->createItem([
            'name' => 'Zero Amount Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $zeroAmountBoon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 0,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(2, $levelUpValue['level']);
        $this->assertSame(2, $levelUpValue['str']);
        $this->assertSame(2, $levelUpValue['dur']);
        $this->assertSame(3, $levelUpValue['dex']);
        $this->assertSame(2, $levelUpValue['chr']);
        $this->assertSame(2, $levelUpValue['int']);
        $this->assertSame(2, $levelUpValue['agi']);
        $this->assertSame(2, $levelUpValue['focus']);
    }

    public function test_stats_at_max_stat_cap_do_not_increase_past_project_max_stat_constant(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'str' => MaxReincarnationStats::MAX_STATS,
            'dur' => MaxReincarnationStats::MAX_STATS,
            'dex' => MaxReincarnationStats::MAX_STATS,
            'chr' => MaxReincarnationStats::MAX_STATS,
            'int' => MaxReincarnationStats::MAX_STATS,
            'agi' => MaxReincarnationStats::MAX_STATS,
            'focus' => MaxReincarnationStats::MAX_STATS,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['str']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['dur']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['dex']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['chr']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['int']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['agi']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['focus']);
    }

    public function test_normal_stat_one_below_max_stat_cap_does_not_increase_past_project_max_stat_constant(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'str' => MaxReincarnationStats::MAX_STATS - 1,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['str']);
    }

    public function test_damage_stat_one_below_max_stat_cap_does_not_increase_past_project_max_stat_constant(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'dex' => MaxReincarnationStats::MAX_STATS - 1,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['dex']);
    }

    public function test_capped_normal_stats_increase_base_stat_modifier_by_one_level_without_boon(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'str' => MaxReincarnationStats::MAX_STATS,
            'base_stat_mod' => 0.25,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertEqualsWithDelta(0.25012, $levelUpValue['base_stat_mod'], 0.00000001);
    }

    public function test_capped_damage_stat_increases_base_damage_stat_modifier_by_one_level_without_boon(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'dex' => MaxReincarnationStats::MAX_STATS,
            'base_damage_stat_mod' => 0.25,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertEqualsWithDelta(0.2501, $levelUpValue['base_damage_stat_mod'], 0.00000001);
    }

    public function test_capped_normal_stats_increase_base_stat_modifier_by_five_levels_with_stackable_extra_level_boon(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Capped Normal Stat Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->update([
            'str' => MaxReincarnationStats::MAX_STATS,
            'base_stat_mod' => 0.25,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertEqualsWithDelta(0.2506, $levelUpValue['base_stat_mod'], 0.00000001);
    }

    public function test_capped_damage_stat_increases_base_damage_stat_modifier_by_five_levels_with_stackable_extra_level_boon(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Capped Damage Stat Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->update([
            'dex' => MaxReincarnationStats::MAX_STATS,
            'base_damage_stat_mod' => 0.25,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertEqualsWithDelta(0.2505, $levelUpValue['base_damage_stat_mod'], 0.00000001);
    }

    public function test_base_stat_modifier_does_not_exceed_cap(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'str' => MaxReincarnationStats::MAX_STATS,
            'base_stat_mod' => 0.59999,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(0.60, $levelUpValue['base_stat_mod']);
    }

    public function test_base_damage_stat_modifier_does_not_exceed_cap(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'dex' => MaxReincarnationStats::MAX_STATS,
            'base_damage_stat_mod' => 0.49999,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(0.50, $levelUpValue['base_damage_stat_mod']);
    }

    public function test_returned_base_stat_modifier_uses_internal_cap(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'str' => MaxReincarnationStats::MAX_STATS,
            'base_stat_mod' => 0.75,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(0.60, $levelUpValue['base_stat_mod']);
    }

    public function test_returned_base_damage_stat_modifier_uses_internal_cap(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'dex' => MaxReincarnationStats::MAX_STATS,
            'base_damage_stat_mod' => 0.75,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(0.50, $levelUpValue['base_damage_stat_mod']);
    }

    public function test_returned_base_stat_modifier_is_capped_when_normal_stat_is_not_capped(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'str' => 1,
            'base_stat_mod' => 0.75,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(0.60, $levelUpValue['base_stat_mod']);
    }

    public function test_returned_base_damage_stat_modifier_is_capped_when_damage_stat_is_not_capped(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'dex' => 1,
            'base_damage_stat_mod' => 0.75,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(0.50, $levelUpValue['base_damage_stat_mod']);
    }

    public function test_character_already_at_max_level_with_capped_stats_gains_no_modifier_progress(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Max Level Modifier Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->update([
            'level' => 1000,
            'str' => MaxReincarnationStats::MAX_STATS,
            'dex' => MaxReincarnationStats::MAX_STATS,
            'base_stat_mod' => 0.25,
            'base_damage_stat_mod' => 0.35,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(1000, $levelUpValue['level']);
        $this->assertSame(0.25, $levelUpValue['base_stat_mod']);
        $this->assertSame(0.35, $levelUpValue['base_damage_stat_mod']);
    }

    public function test_character_already_at_max_level_gains_no_extra_raw_stats(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Max Level Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->update([
            'level' => 1000,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(1000, $levelUpValue['level']);
        $this->assertSame(1, $levelUpValue['str']);
        $this->assertSame(1, $levelUpValue['dur']);
        $this->assertSame(1, $levelUpValue['dex']);
        $this->assertSame(1, $levelUpValue['chr']);
        $this->assertSame(1, $levelUpValue['int']);
        $this->assertSame(1, $levelUpValue['agi']);
        $this->assertSame(1, $levelUpValue['focus']);
    }

    public function test_extra_level_boon_with_null_amount_used_does_not_break_raw_stat_gains(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Null Amount Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        DB::statement('ALTER TABLE character_boons MODIFY amount_used INT NULL');

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => null,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(2, $levelUpValue['level']);
        $this->assertSame(2, $levelUpValue['str']);
        $this->assertSame(3, $levelUpValue['dex']);
    }
}
