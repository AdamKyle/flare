<?php

namespace Tests\Unit\Flare\Items\Comparison;

use App\Flare\Items\Comparison\Comparator;
use App\Flare\Items\Enricher\EquippableEnricher;
use App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema;
use App\Flare\Models\HolyStack;
use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

final class ComparatorTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Comparator $comparator = null;

    private ?EquippableEnricher $enricher = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->comparator = $this->app->make(Comparator::class);
        $this->enricher = $this->app->make(EquippableEnricher::class);
    }

    protected function tearDown(): void
    {
        $this->comparator = null;
        $this->enricher = null;

        parent::tearDown();
    }

    public function test_compare_no_affixes_vs_with_affixes(): void
    {
        $plainBlade = $this->newSword(['name' => 'Plain Blade', 'description' => 'No affixes.']);
        $augmented = $this->newSword(['name' => 'Augmented Blade', 'description' => 'Has affixes.']);

        $plainBlade->update([
            'base_damage' => 100,
            'base_healing' => 30,
            'base_ac' => 10,
            'base_damage_mod' => 0.10,
            'base_healing_mod' => 0.10,
            'base_ac_mod' => 0.10,
            'devouring_light' => 0.05,
        ]);

        $prefixAffix = $this->createItemAffix([
            'base_damage_mod' => 0.10,
            'base_healing_mod' => 0.0,
            'base_ac_mod' => 0.0,
            'devouring_light' => 0.05,
            'damage_amount' => 7,
            'damage_can_stack' => true,
            'irresistible_damage' => false,
            'skill_name' => 'Alchemy',
            'skill_bonus' => 0.10,
            'skill_training_bonus' => 0.05,
        ]);

        $suffixAffix = $this->createItemAffix([
            'base_damage_mod' => 0.15,
            'base_healing_mod' => 0.0,
            'base_ac_mod' => 0.0,
            'devouring_light' => 0.10,
            'damage_amount' => 5,
            'damage_can_stack' => false,
            'irresistible_damage' => true,
        ]);

        $augmented->update([
            'base_damage' => 100,
            'base_healing' => 30,
            'base_ac' => 10,
            'base_damage_mod' => 0.10,
            'base_healing_mod' => 0.10,
            'base_ac_mod' => 0.10,
            'devouring_light' => 0.00,
            'item_prefix_id' => $prefixAffix->id,
            'item_suffix_id' => $suffixAffix->id,
        ]);

        $plainBlade = $this->enricher->enrich($plainBlade->fresh());
        $augmented = $this->enricher->enrich($augmented->fresh());

        $comparisonResult = $this->comparator->compare($plainBlade, $augmented);

        $this->assertSame('Plain Blade', $comparisonResult['comparison']['name']);
        $this->assertSame('No affixes.', $comparisonResult['comparison']['description']);

        $adjustments = $comparisonResult['comparison']['adjustments'];

        $this->assertSame(110.0 - 135.0, $adjustments['total_damage_adjustment']);
        $this->assertSame(0.0, $adjustments['total_healing_adjustment']);
        $this->assertSame(0.0, $adjustments['total_defence_adjustment']);

        $this->assertEqualsWithDelta(-0.25, $adjustments['base_damage_mod_adjustment'], 1e-9);
        $this->assertEqualsWithDelta(-0.10, $adjustments['devouring_light_adjustment'], 1e-9);

        $this->assertSame(-7.0, $adjustments['stackable_adjustment']);
        $this->assertSame(-5.0, $adjustments['non_stacking_adjustment']);
        $this->assertSame(-5.0, $adjustments['irresistible_adjustment']);

        $skillRows = $adjustments['skill_summary'];
        $this->assertCount(1, $skillRows);
        $this->assertSame('Alchemy', $skillRows[0]['skill_name']);
        $this->assertEqualsWithDelta(-0.10, $skillRows[0]['skill_bonus_adjustment'], 1e-9);
        $this->assertEqualsWithDelta(-0.05, $skillRows[0]['skill_training_bonus_adjustment'], 1e-9);
    }

    public function test_compare_affixes_vs_affixes(): void
    {
        $sunblade = $this->newSword(['name' => 'Sunblade',   'description' => 'Bright.']);
        $moonscythe = $this->newSword(['name' => 'Moonscythe', 'description' => 'Cold.']);

        $sunblade->update([
            'base_damage' => 120,
            'base_healing' => 40,
            'base_ac' => 12,
            'base_damage_mod' => 0.05,
            'base_healing_mod' => 0.10,
            'base_ac_mod' => 0.05,
            'devouring_light' => 0.02,
        ]);

        $moonscythe->update([
            'base_damage' => 110,
            'base_healing' => 30,
            'base_ac' => 10,
            'base_damage_mod' => 0.02,
            'base_healing_mod' => 0.05,
            'base_ac_mod' => 0.01,
            'devouring_light' => 0.00,
        ]);

        $leftPrefixAffix = $this->createItemAffix([
            'base_damage_mod' => 0.10,
            'base_healing_mod' => 0.00,
            'base_ac_mod' => 0.00,
            'devouring_light' => 0.03,
            'damage_amount' => 4,
            'damage_can_stack' => true,
            'irresistible_damage' => false,
            'skill_name' => 'Alchemy',
            'skill_bonus' => 0.05,
            'skill_training_bonus' => 0.02,
        ]);
        $leftSuffixAffix = $this->createItemAffix([
            'base_damage_mod' => 0.05,
            'base_healing_mod' => 0.00,
            'base_ac_mod' => 0.00,
            'devouring_light' => 0.01,
            'damage_amount' => 3,
            'damage_can_stack' => false,
            'irresistible_damage' => true,
            'skill_name' => 'Crafting',
            'skill_bonus' => 0.02,
            'skill_training_bonus' => 0.01,
        ]);
        $sunblade->update(['item_prefix_id' => $leftPrefixAffix->id, 'item_suffix_id' => $leftSuffixAffix->id]);

        $rightPrefixAffix = $this->createItemAffix([
            'base_damage_mod' => 0.08,
            'base_healing_mod' => 0.00,
            'base_ac_mod' => 0.00,
            'devouring_light' => 0.02,
            'damage_amount' => 2,
            'damage_can_stack' => true,
            'irresistible_damage' => false,
            'skill_name' => 'Alchemy',
            'skill_bonus' => 0.02,
            'skill_training_bonus' => 0.01,
        ]);
        $rightSuffixAffix = $this->createItemAffix([
            'base_damage_mod' => 0.02,
            'base_healing_mod' => 0.00,
            'base_ac_mod' => 0.00,
            'devouring_light' => 0.01,
            'damage_amount' => 6,
            'damage_can_stack' => false,
            'irresistible_damage' => true,
            'skill_name' => 'Smithing',
            'skill_bonus' => 0.03,
            'skill_training_bonus' => 0.02,
        ]);
        $moonscythe->update(['item_prefix_id' => $rightPrefixAffix->id, 'item_suffix_id' => $rightSuffixAffix->id]);

        $sunblade = $this->enricher->enrich($sunblade->fresh());
        $moonscythe = $this->enricher->enrich($moonscythe->fresh());

        $comparisonResult = $this->comparator->compare($sunblade, $moonscythe);
        $adjustments = $comparisonResult['comparison']['adjustments'];

        $this->assertArrayHasKey('total_damage_adjustment', $adjustments);
        $this->assertArrayHasKey('base_damage_mod_adjustment', $adjustments);
        $this->assertArrayHasKey('devouring_light_adjustment', $adjustments);
        $this->assertArrayHasKey('stackable_adjustment', $adjustments);
        $this->assertArrayHasKey('non_stacking_adjustment', $adjustments);
        $this->assertArrayHasKey('irresistible_adjustment', $adjustments);

        $skillsByName = collect($adjustments['skill_summary'])->keyBy('skill_name');
        $this->assertTrue($skillsByName->has('Alchemy'));
        $this->assertTrue($skillsByName->has('Crafting'));
        $this->assertTrue($skillsByName->has('Smithing'));
    }

    public function test_compare_prefix_only_vs_suffix_only(): void
    {
        $prefixOnly = $this->newSword(['name' => 'Prefix Only']);
        $suffixOnly = $this->newSword(['name' => 'Suffix Only']);

        $prefix = $this->createItemAffix([
            'damage_amount' => 5,
            'damage_can_stack' => true,
            'irresistible_damage' => false,
            'base_damage_mod' => 0.00,
            'base_healing_mod' => 0.00,
            'base_ac_mod' => 0.00,
            'devouring_light' => 0.00,
        ]);

        $suffix = $this->createItemAffix([
            'damage_amount' => 9,
            'damage_can_stack' => false,
            'irresistible_damage' => true,
            'base_damage_mod' => 0.00,
            'base_healing_mod' => 0.00,
            'base_ac_mod' => 0.00,
            'devouring_light' => 0.00,
        ]);

        $prefixOnly->update(['item_prefix_id' => $prefix->id]);
        $suffixOnly->update(['item_suffix_id' => $suffix->id]);

        $prefixOnly = $this->enricher->enrich($prefixOnly->fresh());
        $suffixOnly = $this->enricher->enrich($suffixOnly->fresh());

        $result = $this->comparator->compare($prefixOnly, $suffixOnly);
        $adj = $result['comparison']['adjustments'];

        $this->assertSame(5.0, $adj['stackable_adjustment']);
        $this->assertSame(-9.0, $adj['non_stacking_adjustment']);
        $this->assertSame(-9.0, $adj['irresistible_adjustment']);
    }

    public function test_compare_no_affixes_vs_no_affixes(): void
    {
        $bareSteel = $this->newSword(['name' => 'Bare Steel']);
        $bareIron = $this->newSword(['name' => 'Bare Iron']);

        $bareSteel->update([
            'base_damage' => 80,
            'base_healing' => 12,
            'base_ac' => 7,
            'base_damage_mod' => 0.10,
            'base_healing_mod' => 0.05,
            'base_ac_mod' => 0.00,
        ]);

        $bareIron->update([
            'base_damage' => 70,
            'base_healing' => 12,
            'base_ac' => 7,
            'base_damage_mod' => 0.10,
            'base_healing_mod' => 0.05,
            'base_ac_mod' => 0.00,
        ]);

        $bareSteel = $this->enricher->enrich($bareSteel->fresh());
        $bareIron = $this->enricher->enrich($bareIron->fresh());

        $result = $this->comparator->compare($bareSteel, $bareIron);
        $adj = $result['comparison']['adjustments'];

        $expectedDamageDelta = round(80 * 1.10) - round(70 * 1.10);
        $this->assertSame($expectedDamageDelta, $adj['total_damage_adjustment']);
        $this->assertSame([], $adj['skill_summary']);
    }

    public function test_compare_holy_stack_left_only(): void
    {
        $leftWithHoly = $this->newSword(['devouring_darkness' => 0.05]);
        $rightNoHoly = $this->newSword(['devouring_darkness' => 0.05]);

        HolyStack::create([
            'item_id' => $leftWithHoly->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.00,
        ]);

        $leftWithHoly = $this->enricher->enrich($leftWithHoly->fresh());
        $rightNoHoly = $this->enricher->enrich($rightNoHoly->fresh());

        $result = $this->comparator->compare($leftWithHoly, $rightNoHoly);
        $adj = $result['comparison']['adjustments'];

        $this->assertEqualsWithDelta(0.10, $adj['devouring_darkness_adjustment'], 1e-9);
    }

    public function test_compare_holy_stacks_both(): void
    {
        $leftWithHoly = $this->newSword(['devouring_darkness' => 0.00]);
        $rightWithHoly = $this->newSword(['devouring_darkness' => 0.00]);

        HolyStack::create([
            'item_id' => $leftWithHoly->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.00,
        ]);

        HolyStack::create([
            'item_id' => $rightWithHoly->id,
            'devouring_darkness_bonus' => 0.05,
            'stat_increase_bonus' => 0.00,
        ]);

        $leftWithHoly = $this->enricher->enrich($leftWithHoly->fresh());
        $rightWithHoly = $this->enricher->enrich($rightWithHoly->fresh());

        $result = $this->comparator->compare($leftWithHoly, $rightWithHoly);
        $adj = $result['comparison']['adjustments'];

        $this->assertEqualsWithDelta(0.05, $adj['devouring_darkness_adjustment'], 1e-9);
    }

    public function test_compare_holy_stacks_both_with_affixes(): void
    {
        $left = $this->newSword(['devouring_darkness' => 0.02]);
        $right = $this->newSword(['devouring_darkness' => 0.01]);

        HolyStack::create([
            'item_id' => $left->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.00,
        ]);
        HolyStack::create([
            'item_id' => $right->id,
            'devouring_darkness_bonus' => 0.05,
            'stat_increase_bonus' => 0.00,
        ]);

        $leftPrefix = $this->createItemAffix([
            'devouring_light' => 0.03,
            'base_damage_mod' => 0.00,
            'base_healing_mod' => 0.00,
            'base_ac_mod' => 0.00,
        ]);
        $rightSuffix = $this->createItemAffix([
            'devouring_light' => 0.01,
            'base_damage_mod' => 0.00,
            'base_healing_mod' => 0.00,
            'base_ac_mod' => 0.00,
        ]);

        $left->update(['item_prefix_id' => $leftPrefix->id]);
        $right->update(['item_suffix_id' => $rightSuffix->id]);

        $left = $this->enricher->enrich($left->fresh());
        $right = $this->enricher->enrich($right->fresh());

        $result = $this->comparator->compare($left, $right);
        $adj = $result['comparison']['adjustments'];

        $this->assertEqualsWithDelta(0.06, $adj['devouring_darkness_adjustment'], 1e-9);
        $this->assertEqualsWithDelta(0.02, $adj['devouring_light_adjustment'], 1e-9);
    }

    public function test_compare_ambush_and_counter_adjustments(): void
    {
        $scout = $this->newSword(['name' => 'Scout']);
        $guard = $this->newSword(['name' => 'Guard']);

        $scout->ambush_chance = 0.10;
        $guard->ambush_chance = 0.04;
        $scout->counter_reduction = 0.02;
        $guard->counter_reduction = 0.05;

        $scout = $this->enricher->enrich($scout);
        $guard = $this->enricher->enrich($guard);

        $result = $this->comparator->compare($scout, $guard);
        $adj = $result['comparison']['adjustments'];

        $this->assertEqualsWithDelta(0.06, $adj['ambush_chance_adjustment'], 1e-9);
        $this->assertEqualsWithDelta(-0.03, $adj['counter_reduction_adjustment'], 1e-9);
    }

    public function test_ambush_chance_boolean_uses_flag_diff(): void
    {
        $left = $this->newSword();
        $right = $this->newSword();

        $left->ambush_chance = true;
        $right->ambush_chance = false;

        $left = $this->enricher->enrich($left);
        $right = $this->enricher->enrich($right);

        $result = $this->comparator->compare($left, $right);
        $adj = $result['comparison']['adjustments'];

        $this->assertTrue($adj['ambush_chance_adjustment']);
    }

    public function test_ambush_chance_string_yields_noop(): void
    {
        $left = $this->newSword();
        $right = $this->newSword();

        $left->ambush_chance = 'yes';
        $right->ambush_chance = 'no';

        $left = $this->enricher->enrich($left);
        $right = $this->enricher->enrich($right);

        $result = $this->comparator->compare($left, $right);
        $adj = $result['comparison']['adjustments'];

        $this->assertNull($adj['ambush_chance_adjustment']);
    }

    public function test_included_field_that_also_matches_exclude_is_skipped(): void
    {
        $left = $this->newSword();
        $right = $this->newSword();

        $left->total_damage_id = 123;
        $right->total_damage_id = 456;

        $left = $this->enricher->enrich($left);
        $right = $this->enricher->enrich($right);

        $result = $this->comparator->compare($left, $right);
        $adj = $result['comparison']['adjustments'];

        $this->assertArrayNotHasKey('total_damage_id_adjustment', $adj);
    }

    public function test_default_strategy_runs_when_type_is_unknown(): void
    {
        $schema = $this->app->make(\App\Flare\Items\Comparison\Comparator::class);

        $left = $this->newSword(['name' => 'Left']);
        $right = $this->newSword(['name' => 'Right']);

        $left->ambush_chance = ['not', 'a', 'number'];
        $right->ambush_chance = ['also', 'bad'];

        $result = $this->comparator->compare($left, $right);
        $adjustments = $result['comparison']['adjustments'];

        $this->assertArrayHasKey('ambush_chance_adjustment', $adjustments);
        $this->assertNull($adjustments['ambush_chance_adjustment']);
    }

    public function test_index_rows_by_key_guard_branches(): void
    {
        $comparator = $this->app->make(\App\Flare\Items\Comparison\Comparator::class);

        $left = $this->newSword();
        $right = $this->newSword();

        $left->skill_summary = 'not-an-array';

        $right->skill_summary = [
            123,
            ['wrong_key' => 'x'],
            ['skill_name' => 'Alchemy', 'skill_bonus' => 1, 'skill_training_bonus' => 0],
        ];

        $result = $this->comparator->compare($left, $right);
        $rows = $result['comparison']['adjustments']['skill_summary'];

        $this->assertCount(1, $rows);
        $this->assertSame('Alchemy', $rows[0]['skill_name']);
        $this->assertSame(-1.0, $rows[0]['skill_bonus_adjustment']);
    }

    public function test_default_strategy_fallbacks_for_number_and_boolean(): void
    {
        $schema = Mockery::mock(ManifestSchema::class);
        $schema->shouldReceive('includes')->andReturn(['/^(base_damage|usable)$/']);
        $schema->shouldReceive('excludes')->andReturn([]);
        $schema->shouldReceive('map')->andReturnUsing(function (string $prop) {
            return $prop === 'base_damage' ? 'totals.damage'
                : ($prop === 'usable' ? 'flags.usable' : null);
        });
        $schema->shouldReceive('typeFor')->andReturnUsing(function (string $prop) {
            return $prop === 'base_damage' ? 'number'
                : ($prop === 'usable' ? 'boolean' : null);
        });
        $schema->shouldReceive('compareFor')->andReturnNull();
        $schema->shouldReceive('collections')->andReturn([]);

        $this->app->instance(ManifestSchema::class, $schema);
        $comparator = $this->app->make(Comparator::class);

        $left = $this->newSword(['base_damage' => 10, 'usable' => true]);
        $right = $this->newSword(['base_damage' => 4,  'usable' => false]);

        $out = $comparator->compare($left, $right);
        $adj = $out['comparison']['adjustments'];

        $this->assertSame(6.0, $adj['total_damage_adjustment']);
        $this->assertTrue($adj['usable_adjustment']);
    }

    public function test_map_returning_null_skips_field(): void
    {
        $schema = \Mockery::mock(\App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema::class);
        $schema->shouldReceive('includes')->andReturn(['/^ghost$/']);
        $schema->shouldReceive('excludes')->andReturn([]);
        $schema->shouldReceive('map')->with('ghost')->andReturnNull();
        $schema->shouldReceive('typeFor')->never();
        $schema->shouldReceive('compareFor')->never();
        $schema->shouldReceive('collections')->andReturn([]);

        $this->app->instance(\App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema::class, $schema);
        $comparator = $this->app->make(\App\Flare\Items\Comparison\Comparator::class);

        $left = $this->newSword();
        $left->ghost = 123;
        $right = $this->newSword();
        $right->ghost = 456;

        $result = $comparator->compare($left, $right);
        $adj = $result['comparison']['adjustments'];

        $this->assertArrayNotHasKey('ghost_adjustment', $adj);
    }

    public function test_collections_descriptor_missing_path_is_skipped(): void
    {
        $schema = \Mockery::mock(ManifestSchema::class);
        $schema->shouldReceive('includes')->andReturn([]);
        $schema->shouldReceive('excludes')->andReturn([]);
        $schema->shouldReceive('map')->andReturnNull();
        $schema->shouldReceive('typeFor')->andReturnNull();
        $schema->shouldReceive('compareFor')->andReturnNull();
        $schema->shouldReceive('collections')->andReturn([[
            'prop' => 'skill_summary',
            'key' => 'skill_name',
            'fields' => ['skill_bonus' => 'delta'],
        ]]);

        $this->app->instance(ManifestSchema::class, $schema);
        $comparator = $this->app->make(Comparator::class);

        $left = $this->newSword();
        $right = $this->newSword();
        $right->skill_summary = [
            ['skill_name' => 'Alchemy', 'skill_bonus' => 1],
        ];

        $result = $comparator->compare($left, $right);
        $this->assertSame([], $result['comparison']['adjustments']['skill_summary']);
    }

    public function test_index_rows_by_key_with_no_valid_rows_returns_empty(): void
    {
        $left = $this->newSword();
        $right = $this->newSword();

        $left->skill_summary = [123, ['wrong_key' => 'x']];
        $right->skill_summary = [['also_wrong' => 'y']];

        $result = $this->comparator->compare($left, $right);
        $rows = $result['comparison']['adjustments']['skill_summary'];

        $this->assertSame([], $rows);
    }

    public function test_holy_stack_specific_adjustments_are_compared(): void
    {
        $left = $this->newSword();
        $right = $this->newSword();

        HolyStack::create([
            'item_id' => $left->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.02,
        ]);

        HolyStack::create([
            'item_id' => $left->id,
            'devouring_darkness_bonus' => 0.05,
            'stat_increase_bonus' => 0.01,
        ]);

        HolyStack::create([
            'item_id' => $right->id,
            'devouring_darkness_bonus' => 0.05,
            'stat_increase_bonus' => 0.01,
        ]);

        $left = $this->enricher->enrich($left->fresh());
        $right = $this->enricher->enrich($right->fresh());

        $out = $this->comparator->compare($left, $right);
        $adj = $out['comparison']['adjustments'];

        $this->assertArrayHasKey('holy_stack_devouring_darkness_adjustment', $adj);
        $this->assertArrayHasKey('holy_stack_stat_bonus_adjustment', $adj);
        $this->assertArrayHasKey('holy_stacks_applied_adjustment', $adj);

        $this->assertEqualsWithDelta(0.10, $adj['holy_stack_devouring_darkness_adjustment'], 1e-9);
        $this->assertEqualsWithDelta(0.02, $adj['holy_stack_stat_bonus_adjustment'], 1e-9);
        $this->assertSame(1.0, $adj['holy_stacks_applied_adjustment']);
    }

    private function newSword(array $attributes = []): Item
    {
        $defaults = [
            'type' => 'sword',
            'usable' => false,
            'name' => 'Sword',
            'description' => '',
            'base_damage' => 0,
            'base_healing' => 0,
            'base_ac' => 0,
            'base_damage_mod' => 0.0,
            'base_healing_mod' => 0.0,
            'base_ac_mod' => 0.0,
            'devouring_light' => 0.0,
            'devouring_darkness' => 0.0,
        ];

        return $this->createItem(array_merge($defaults, $attributes));
    }

    public function test_ring_resistances_adjustments_are_compared(): void
    {
        $equipped = $this->createItem([
            'type' => 'ring',
            'name' => 'Equipped Ring',
            'spell_evasion' => 0.00,
            'healing_reduction' => 0.00,
            'affix_damage_reduction' => 0.00,
        ]);

        $candidate = $this->createItem([
            'type' => 'ring',
            'name' => 'Candidate Ring',
            'spell_evasion' => 0.25,
            'healing_reduction' => 0.05,
            'affix_damage_reduction' => 0.11,
        ]);

        $equipped = $this->enricher->enrich($equipped->fresh());
        $candidate = $this->enricher->enrich($candidate->fresh());

        $out = $this->comparator->compare($candidate, $equipped);
        $adj = $out['comparison']['adjustments'];

        $this->assertArrayHasKey('spell_evasion_adjustment', $adj);
        $this->assertArrayHasKey('healing_reduction_adjustment', $adj);
        $this->assertArrayHasKey('affix_damage_reduction_adjustment', $adj);

        $this->assertEqualsWithDelta(0.25, $adj['spell_evasion_adjustment'], 1e-9);
        $this->assertEqualsWithDelta(0.05, $adj['healing_reduction_adjustment'], 1e-9);
        $this->assertEqualsWithDelta(0.11, $adj['affix_damage_reduction_adjustment'], 1e-9);
    }

    public function test_healing_spell_resurrection_chance_adjustment_is_compared(): void
    {
        $equipped = $this->createItem([
            'type' => 'spell-healing',
            'name' => 'Equipped Heal',
            'resurrection_chance' => 0.10,
        ]);

        $candidate = $this->createItem([
            'type' => 'spell-healing',
            'name' => 'Candidate Heal',
            'resurrection_chance' => 0.15,
        ]);

        $equipped = $this->enricher->enrich($equipped->fresh());
        $candidate = $this->enricher->enrich($candidate->fresh());

        $out = $this->comparator->compare($candidate, $equipped);
        $adj = $out['comparison']['adjustments'];

        $this->assertArrayHasKey('resurrection_chance_adjustment', $adj);
        $this->assertEqualsWithDelta(0.05, $adj['resurrection_chance_adjustment'], 1e-9);
    }

    public function test_numeric_string_resistances_are_treated_as_numbers(): void
    {
        $equipped = $this->createItem([
            'type' => 'ring',
            'name' => 'Equipped Ring',
            'affix_damage_reduction' => '1.0000',
            'healing_reduction' => '0.1000',
        ]);

        $candidate = $this->createItem([
            'type' => 'ring',
            'name' => 'Candidate Ring',
            'affix_damage_reduction' => 0.11,
            'healing_reduction' => 0.00,
        ]);

        $equipped = $this->enricher->enrich($equipped->fresh());
        $candidate = $this->enricher->enrich($candidate->fresh());

        $out = $this->comparator->compare($candidate, $equipped);
        $adj = $out['comparison']['adjustments'];

        $this->assertEqualsWithDelta(-0.89, $adj['affix_damage_reduction_adjustment'], 1e-9);
        $this->assertEqualsWithDelta(-0.10, $adj['healing_reduction_adjustment'], 1e-9);
    }
}
