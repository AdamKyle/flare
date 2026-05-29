<?php

namespace Tests\Console;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CharacterRepairStatsCommandTest extends TestCase
{
    use CreateClass, CreateRace, CreateUser, RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dry_run_scans_affected_characters_but_changes_no_stats(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 6,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats'));

        $output = Artisan::output();

        $this->assertStringContainsString('Characters scanned: 1', $output);
        $this->assertStringContainsString('Characters affected: 1', $output);
        $this->assertStringContainsString('Total stat points to add: 116', $output);
        $this->assertStringContainsString('str: 14', $output);
        $this->assertStringContainsString('dur: 17', $output);
        $this->assertStringContainsString('dex: 17', $output);
        $this->assertStringContainsString('chr: 17', $output);
        $this->assertStringContainsString('int: 17', $output);
        $this->assertStringContainsString('agi: 17', $output);
        $this->assertStringContainsString('focus: 17', $output);
        $this->assertStringContainsString('character_id', $output);
        $this->assertStringContainsString('character_name', $output);
        $this->assertStringContainsString('current_reincarnated_stat_increase', $output);
        $this->assertStringContainsString('expected_reincarnated_stat_increase', $output);
        $this->assertStringContainsString('reincarnation_bonus_missing', $output);
        $this->assertStringContainsString('raw_stats_missing_total', $output);
        $this->assertStringContainsString('stats_to_repair', $output);
        $this->assertStringContainsString((string) $character->id, $output);
        $this->assertStringContainsString($character->name, $output);
        $this->assertStringContainsString('str +14, dur +17, dex +17, chr +17, int +17, agi +17, focus +17', $output);
        $this->assertStringContainsString('will change raw stats: str +14, dur +17, dex +17, chr +17, int +17, agi +17, focus +17', $output);

        $character = $character->refresh();

        $this->assertSame(4, $character->str);
        $this->assertSame(6, $character->dex);
    }

    public function test_apply_repairs_under_statted_characters(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 6,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
        ]));

        $output = Artisan::output();
        $character = $character->refresh();

        $this->assertStringContainsString('character_id', $output);
        $this->assertStringContainsString('character_name', $output);
        $this->assertStringContainsString('stats_to_repair', $output);
        $this->assertStringContainsString((string) $character->id, $output);
        $this->assertStringContainsString($character->name, $output);
        $this->assertStringContainsString('fixed raw stats: str +14, dur +17, dex +17, chr +17, int +17, agi +17, focus +17', $output);
        $this->assertSame(18, $character->str);
        $this->assertSame(23, $character->dex);
    }

    public function test_apply_leaves_correctly_statted_characters_unchanged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-24 09:00:00'));

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

        $updatedAtBeforeCommand = $character->refresh()->updated_at;

        Carbon::setTestNow(Carbon::parse('2026-05-24 10:00:00'));

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
        ]));

        $output = Artisan::output();
        $character = $character->refresh();

        $this->assertStringContainsString('No affected characters found.', $output);
        $this->assertSame(18, $character->str);
        $this->assertSame(23, $character->dex);
        $this->assertTrue($updatedAtBeforeCommand->eq($character->updated_at));
    }

    public function test_command_processes_more_than_one_hundred_characters(): void
    {
        $race = $this->createRace();
        $class = $this->createClass([
            'damage_stat' => 'dex',
        ]);
        $user = $this->createUser();

        Character::factory()
            ->count(101)
            ->sequence(fn (Sequence $sequence) => [
                'name' => 'repair-stat-'.$sequence->index,
            ])
            ->create([
                'user_id' => $user->id,
                'game_race_id' => $race->id,
                'game_class_id' => $class->id,
                'damage_stat' => 'dex',
                'level' => 6,
                'reincarnated_stat_increase' => 3,
                'str' => 4,
                'dex' => 6,
            ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
        ]));

        $this->assertSame(101, Character::where('str', 18)->where('dex', 23)->count());
    }

    public function test_command_does_not_change_xp_level_currencies_or_reincarnation_count(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'xp' => 75,
            'xp_next' => 250,
            'gold' => 123,
            'gold_dust' => 456,
            'shards' => 789,
            'copper_coins' => 321,
            'times_reincarnated' => 2,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 6,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
        ]));

        $character = $character->refresh();

        $this->assertSame(6, $character->level);
        $this->assertSame(75, $character->xp);
        $this->assertSame(250, $character->xp_next);
        $this->assertSame(123, $character->gold);
        $this->assertSame(456, $character->gold_dust);
        $this->assertSame(789, $character->shards);
        $this->assertSame(321, $character->copper_coins);
        $this->assertSame(2, $character->times_reincarnated);
    }

    public function test_running_apply_twice_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-24 09:00:00'));

        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 6,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
        ]));

        $character = $character->refresh();
        $updatedAtAfterFirstRun = $character->updated_at;

        Carbon::setTestNow(Carbon::parse('2026-05-24 10:00:00'));

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
        ]));

        $character = $character->refresh();

        $this->assertSame(18, $character->str);
        $this->assertSame(23, $character->dex);
        $this->assertTrue($updatedAtAfterFirstRun->eq($character->updated_at));
    }

    public function test_dry_run_reports_skipped_count_for_one_bad_character(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('characters')->where('id', $character->id)->update([
            'game_class_id' => 999999,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->assertEquals(0, Artisan::call('characters:repair-stats'));

        $output = Artisan::output();

        $this->assertStringContainsString('Characters scanned: 1', $output);
        $this->assertStringContainsString('Characters skipped: 1', $output);
        $this->assertStringContainsString('Skipped character '.$character->id, $output);
        $this->assertStringContainsString($character->name, $output);
    }

    public function test_apply_reports_skipped_count_for_one_bad_character(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('characters')->where('id', $character->id)->update([
            'game_class_id' => 999999,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
        ]));

        $output = Artisan::output();

        $this->assertStringContainsString('Characters scanned: 1', $output);
        $this->assertStringContainsString('Characters skipped: 1', $output);
        $this->assertStringContainsString('Skipped character '.$character->id, $output);
        $this->assertStringContainsString($character->name, $output);
    }

    public function test_one_bad_character_does_not_stop_later_valid_characters_from_being_scanned(): void
    {
        $badCharacter = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('characters')->where('id', $badCharacter->id)->update([
            'game_class_id' => 999999,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $validCharacter = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $validCharacter->update([
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

        $this->assertEquals(0, Artisan::call('characters:repair-stats'));

        $output = Artisan::output();

        $this->assertStringContainsString('Characters scanned: 2', $output);
        $this->assertStringContainsString('Characters skipped: 1', $output);
    }

    public function test_one_bad_character_does_not_stop_later_valid_affected_characters_from_being_repaired(): void
    {
        $badCharacter = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('characters')->where('id', $badCharacter->id)->update([
            'game_class_id' => 999999,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $affectedCharacter = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $affectedCharacter->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 6,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
        ]));

        $affectedCharacter = $affectedCharacter->refresh();
        $output = Artisan::output();

        $this->assertStringContainsString('Characters scanned: 2', $output);
        $this->assertStringContainsString('Characters skipped: 1', $output);
        $this->assertSame(18, $affectedCharacter->str);
        $this->assertSame(23, $affectedCharacter->dex);
    }

    public function test_dry_run_prints_audit_detail_for_largest_correction(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'reincarnated_stat_increase' => 3,
            'str' => 4,
            'dex' => 6,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats'));

        $output = Artisan::output();

        $this->assertStringContainsString('Largest correction: 116', $output);
        $this->assertStringContainsString('character '.$character->id, $output);
        $this->assertStringContainsString($character->name, $output);
    }

    public function test_dry_run_reports_reincarnation_bonus_gap_but_changes_nothing(): void
    {
        MaxLevelConfiguration::create([
            'max_level' => 2000,
            'half_way' => 1000,
            'three_quarters' => 1500,
            'last_leg' => 1900,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'times_reincarnated' => 2,
            'reincarnated_stat_increase' => 50,
            'str' => 65,
            'dex' => 70,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--repair-reincarnation-bonus' => true,
        ]));

        $output = Artisan::output();
        $character = $character->refresh();

        $this->assertStringContainsString('Characters affected: 1', $output);
        $this->assertStringContainsString('Total stat points to add: 1405', $output);
        $this->assertStringContainsString('Total reincarnation bonus gap: 155', $output);
        $this->assertStringContainsString('str: 155', $output);
        $this->assertStringContainsString('dur: 219', $output);
        $this->assertStringContainsString('dex: 155', $output);
        $this->assertStringContainsString('chr: 219', $output);
        $this->assertStringContainsString('int: 219', $output);
        $this->assertStringContainsString('agi: 219', $output);
        $this->assertStringContainsString('focus: 219', $output);
        $this->assertStringContainsString('raw_stats_missing_total', $output);
        $this->assertStringContainsString('1405', $output);
        $this->assertStringContainsString('str +155, dur +219, dex +155, chr +219, int +219, agi +219, focus +219', $output);
        $this->assertStringContainsString('will change reincarnated_stat_increase 50 -> 205; raw stats: str +155, dur +219, dex +155, chr +219, int +219, agi +219, focus +219', $output);
        $this->assertStringContainsString('Largest correction: 1405', $output);
        $this->assertSame(50, $character->reincarnated_stat_increase);
        $this->assertSame(65, $character->str);
        $this->assertSame(70, $character->dex);
    }

    public function test_apply_repairs_reincarnation_bonus_then_raw_stats(): void
    {
        MaxLevelConfiguration::create([
            'max_level' => 2000,
            'half_way' => 1000,
            'three_quarters' => 1500,
            'last_leg' => 1900,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'xp' => 75,
            'xp_next' => 250,
            'gold' => 123,
            'gold_dust' => 456,
            'shards' => 789,
            'copper_coins' => 321,
            'times_reincarnated' => 2,
            'reincarnated_stat_increase' => 50,
            'str' => 65,
            'dex' => 70,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
            '--repair-reincarnation-bonus' => true,
        ]));

        $character = $character->refresh();

        $this->assertSame(205, $character->reincarnated_stat_increase);
        $this->assertSame(220, $character->str);
        $this->assertSame(225, $character->dex);
        $this->assertSame(6, $character->level);
        $this->assertSame(75, $character->xp);
        $this->assertSame(250, $character->xp_next);
        $this->assertSame(123, $character->gold);
        $this->assertSame(456, $character->gold_dust);
        $this->assertSame(789, $character->shards);
        $this->assertSame(321, $character->copper_coins);
        $this->assertSame(2, $character->times_reincarnated);
    }

    public function test_repair_reincarnation_bonus_does_not_reduce_existing_bonus(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-24 09:00:00'));

        MaxLevelConfiguration::create([
            'max_level' => 2000,
            'half_way' => 1000,
            'three_quarters' => 1500,
            'last_leg' => 1900,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'times_reincarnated' => 2,
            'reincarnated_stat_increase' => 250,
            'str' => 265,
            'dur' => 265,
            'dex' => 270,
            'chr' => 265,
            'int' => 265,
            'agi' => 265,
            'focus' => 265,
        ]);

        $updatedAtBeforeCommand = $character->refresh()->updated_at;

        Carbon::setTestNow(Carbon::parse('2026-05-24 10:00:00'));

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
            '--repair-reincarnation-bonus' => true,
        ]));

        $output = Artisan::output();
        $character = $character->refresh();

        $this->assertStringContainsString('Total reincarnation bonus gap: 0', $output);
        $this->assertSame(250, $character->reincarnated_stat_increase);
        $this->assertSame(265, $character->str);
        $this->assertSame(270, $character->dex);
        $this->assertTrue($updatedAtBeforeCommand->eq($character->updated_at));
    }

    public function test_repair_reincarnation_bonus_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-24 09:00:00'));

        MaxLevelConfiguration::create([
            'max_level' => 2000,
            'half_way' => 1000,
            'three_quarters' => 1500,
            'last_leg' => 1900,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'level' => 6,
            'times_reincarnated' => 2,
            'reincarnated_stat_increase' => 50,
            'str' => 65,
            'dex' => 70,
        ]);

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
            '--repair-reincarnation-bonus' => true,
        ]));

        $character = $character->refresh();
        $updatedAtAfterFirstRun = $character->updated_at;

        Carbon::setTestNow(Carbon::parse('2026-05-24 10:00:00'));

        $this->assertEquals(0, Artisan::call('characters:repair-stats', [
            '--apply' => true,
            '--repair-reincarnation-bonus' => true,
        ]));

        $character = $character->refresh();

        $this->assertSame(205, $character->reincarnated_stat_increase);
        $this->assertSame(220, $character->str);
        $this->assertSame(225, $character->dex);
        $this->assertTrue($updatedAtAfterFirstRun->eq($character->updated_at));
    }
}
