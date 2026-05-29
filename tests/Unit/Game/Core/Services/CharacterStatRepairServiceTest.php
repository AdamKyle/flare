<?php

namespace Tests\Unit\Game\Core\Services;

use App\Game\Core\Services\CharacterStatRepairService;
use App\Game\Reincarnate\Values\MaxReincarnationStats;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use TypeError;

class CharacterStatRepairServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_under_statted_character_is_repaired_to_expected_floor(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dur' => 5,
            'dex' => 6,
            'chr' => 7,
            'int' => 8,
            'agi' => 9,
            'focus' => 10,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(18, $character->str);
        $this->assertSame(18, $character->dur);
        $this->assertSame(23, $character->dex);
        $this->assertSame(18, $character->chr);
        $this->assertSame(18, $character->int);
        $this->assertSame(18, $character->agi);
        $this->assertSame(18, $character->focus);
    }

    public function test_correctly_statted_character_is_unchanged(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 18,
            'dur' => 18,
            'dex' => 23,
            'chr' => 18,
            'int' => 18,
            'agi' => 18,
            'focus' => 18,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(18, $character->str);
        $this->assertSame(18, $character->dur);
        $this->assertSame(23, $character->dex);
        $this->assertSame(18, $character->chr);
        $this->assertSame(18, $character->int);
        $this->assertSame(18, $character->agi);
        $this->assertSame(18, $character->focus);
    }

    public function test_over_statted_character_is_unchanged(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 28,
            'dur' => 29,
            'dex' => 33,
            'chr' => 30,
            'int' => 31,
            'agi' => 32,
            'focus' => 34,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(28, $character->str);
        $this->assertSame(29, $character->dur);
        $this->assertSame(33, $character->dex);
        $this->assertSame(30, $character->chr);
        $this->assertSame(31, $character->int);
        $this->assertSame(32, $character->agi);
        $this->assertSame(34, $character->focus);
    }

    public function test_damage_stat_receives_double_level_up_floor(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'str'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 4,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(23, $character->str);
        $this->assertSame(18, $character->dex);
    }

    public function test_max_stat_cap_is_respected(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 3,
            'reincarnated_stat_increase' => MaxReincarnationStats::MAX_STATS - 10,
            'str' => MaxReincarnationStats::MAX_STATS - 1,
            'dex' => MaxReincarnationStats::MAX_STATS - 1,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(MaxReincarnationStats::MAX_STATS, $character->str);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $character->dex);
    }

    public function test_running_repair_twice_causes_no_second_change(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-23 09:00:00'));

        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 6,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();
        $updatedAtAfterFirstRepair = $character->updated_at;

        Carbon::setTestNow(Carbon::parse('2026-05-23 10:00:00'));

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(18, $character->str);
        $this->assertSame(23, $character->dex);
        $this->assertTrue($updatedAtAfterFirstRepair->eq($character->updated_at));
    }

    public function test_race_base_stats_are_included_in_repaired_floor(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(
                raceOptions: ['str_mod' => 5],
                classOptions: ['damage_stat' => 'dex'],
                assignBaseSkill: false,
                assignPassiveSkills: false
            )
            ->getCharacter();

        $character->update([
            'level' => 2,
            'reincarnated_stat_increase' => 1,
            'str' => 1,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(17, $character->str);
    }

    public function test_class_base_stats_are_included_in_repaired_floor(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(
                classOptions: [
                    'damage_stat' => 'dex',
                    'str_mod' => 4,
                ],
                assignBaseSkill: false,
                assignPassiveSkills: false
            )
            ->getCharacter();

        $character->update([
            'level' => 2,
            'reincarnated_stat_increase' => 1,
            'str' => 1,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(16, $character->str);
    }

    public function test_level_one_characters_use_zero_level_ups(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 1,
            'reincarnated_stat_increase' => 3,
            'str' => 1,
            'dex' => 1,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(13, $character->str);
        $this->assertSame(13, $character->dex);
    }

    public function test_level_zero_characters_do_not_produce_negative_level_up_repair(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 0,
            'reincarnated_stat_increase' => 3,
            'str' => 1,
            'dex' => 1,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(13, $character->str);
        $this->assertSame(13, $character->dex);
    }

    public function test_invalid_damage_stat_does_not_crash_repair_or_corrupt_valid_stats(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'damage_stat' => 'invalid',
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 30,
        ]);

        resolve(CharacterStatRepairService::class)->repair($character->refresh());

        $character = $character->refresh();

        $this->assertSame(18, $character->str);
        $this->assertSame(30, $character->dex);
    }

    public function test_missing_race_relation_throws_existing_base_stat_type_error(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->setRelation('race', null);

        $this->expectException(TypeError::class);

        resolve(CharacterStatRepairService::class)->repair($character);
    }

    public function test_missing_class_relation_throws_existing_base_stat_type_error(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->setRelation('class', null);

        $this->expectException(TypeError::class);

        resolve(CharacterStatRepairService::class)->repair($character);
    }
}
