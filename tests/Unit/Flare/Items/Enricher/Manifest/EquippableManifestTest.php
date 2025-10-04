<?php

namespace Tests\Unit\Flare\Items\Enricher\Manifest;

use App\Flare\Items\Enricher\Manifest\EquippableManifest;
use Tests\TestCase;

final class EquippableManifestTest extends TestCase
{
    private ?EquippableManifest $schema = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema = $this->app->make(EquippableManifest::class);
    }

    protected function tearDown(): void
    {
        $this->schema = null;
        parent::tearDown();
    }

    public function test_includes_patterns_return_expected_list(): void
    {
        $expected = [
            '/^total_.+$/',
            '/^base_.+_mod$/',
            '/^devouring_.+$/',
            '/^.*_chance$/',
            '/^.*_reduction$/',
            '/^.*_evasion$/',
            '/^total_.*_affix_damage$/',
            '/^(str|dur|dex|chr|int|agi|focus)_mod$/',
        ];

        $this->assertSame($expected, $this->schema->includes());

        $samples = [
            'total_damage',
            'base_damage_mod',
            'devouring_light',
            'ambush_chance',
            'str_reduction',
            'dodge_evasion',
            'total_irresistible_affix_damage',
            'str_mod',
        ];

        foreach ($samples as $prop) {
            $this->assertTrue(
                $this->matchesAny($prop, $expected),
                "Expected at least one include pattern to match: {$prop}"
            );
        }
    }

    public function test_excludes_patterns_return_expected_list(): void
    {
        $expected = ['/^id$/', '/_id$/'];
        $this->assertSame($expected, $this->schema->excludes());

        $this->assertTrue($this->matchesAny('id', $expected));
        $this->assertTrue($this->matchesAny('item_id', $expected));
        $this->assertFalse($this->matchesAny('identifier', $expected));
    }

    public function test_map_totals(): void
    {
        foreach ($this->casesTotals() as [$prop, $expected]) {
            $this->assertSame($expected, $this->schema->map($prop));
        }
    }

    public function test_map_base_mods(): void
    {
        foreach ($this->casesBaseMods() as [$prop, $expected]) {
            $this->assertSame($expected, $this->schema->map($prop));
        }
    }

    public function test_map_devouring(): void
    {
        foreach ($this->casesDevouring() as [$prop, $expected]) {
            $this->assertSame($expected, $this->schema->map($prop));
        }
    }

    public function test_map_affix_damage(): void
    {
        foreach ($this->casesAffixDamage() as [$prop, $expected]) {
            $this->assertSame($expected, $this->schema->map($prop));
        }
    }

    public function test_map_fallback_keeps_original_prop(): void
    {
        $this->assertSame('weird_prop', $this->schema->map('weird_prop'));
    }

    public function test_type_for_covers_all_branches(): void
    {
        $this->assertSame('number', $this->schema->typeFor('p', 1));
        $this->assertSame('number', $this->schema->typeFor('p', 1.5));
        $this->assertSame('boolean', $this->schema->typeFor('p', true));
        $this->assertSame('string', $this->schema->typeFor('p', 'ok'));
        $this->assertNull($this->schema->typeFor('p', ['array']));
    }

    public function test_compare_for_covers_all_branches(): void
    {
        $this->assertSame('delta', $this->schema->compareFor('totals.damage', 'number'));
        $this->assertSame('flag-diff', $this->schema->compareFor('some.flag', 'boolean'));
        $this->assertSame('noop', $this->schema->compareFor('label', 'string'));
        $this->assertSame('noop', $this->schema->compareFor('path', 'unknown'));
    }

    public function test_collections_shape_and_values(): void
    {
        $collections = $this->schema->collections();

        $this->assertIsArray($collections);
        $this->assertCount(1, $collections);

        $c = $collections[0];

        $this->assertSame('skill_summary', $c['path']);
        $this->assertSame('skill_summary', $c['prop']);
        $this->assertSame('skill_name', $c['key']);

        $this->assertIsArray($c['fields']);
        $this->assertArrayHasKey('skill_training_bonus', $c['fields']);
        $this->assertArrayHasKey('skill_bonus', $c['fields']);
        $this->assertSame('delta', $c['fields']['skill_training_bonus']);
        $this->assertSame('delta', $c['fields']['skill_bonus']);
    }

    private function casesTotals(): array
    {
        return [
            ['total_damage', 'totals.damage'],
            ['total_defence', 'totals.defence'],
            ['total_healing', 'totals.healing'],
        ];
    }

    private function casesBaseMods(): array
    {
        return [
            ['base_damage_mod', 'mods.base.damage_mod'],
            ['base_healing_mod', 'mods.base.healing_mod'],
            ['base_ac_mod', 'mods.base.ac_mod'],
        ];
    }

    private function casesDevouring(): array
    {
        return [
            ['devouring_light', 'devouring.light'],
            ['devouring_darkness', 'devouring.darkness'],
        ];
    }

    private function casesAffixDamage(): array
    {
        return [
            ['total_stackable_affix_damage', 'affix_damage.stackable'],
            ['total_non_stacking_affix_damage', 'affix_damage.non_stacking'],
            ['total_irresistible_affix_damage', 'affix_damage.irresistible'],
        ];
    }

    private function matchesAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $ok = @preg_match($pattern, $value);
            if ($ok === 1) {
                return true;
            }
        }

        return false;
    }
}
