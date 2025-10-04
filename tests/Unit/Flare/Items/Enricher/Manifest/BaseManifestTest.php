<?php

declare(strict_types=1);

namespace Tests\Unit\Flare\Items\Enricher\Manifest;

use App\Flare\Items\Enricher\Manifest\BaseManifest;
use PHPUnit\Framework\TestCase;

final class BaseManifestTest extends TestCase
{
    private ?BaseManifest $schema = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Anonymous concrete implementation so we can test BaseManifest defaults.
        $this->schema = new class extends BaseManifest
        {
            // Intentionally no overrides; we want to exercise BaseManifest itself.
        };
    }

    protected function tearDown(): void
    {
        $this->schema = null;
        parent::tearDown();
    }

    public function test_includes_returns_empty_array_by_default(): void
    {
        $this->assertSame([], $this->schema->includes());
    }

    public function test_excludes_returns_empty_array_by_default(): void
    {
        $this->assertSame([], $this->schema->excludes());
    }

    public function test_map_returns_original_prop_by_default(): void
    {
        $this->assertSame('total_damage', $this->schema->map('total_damage'));
        $this->assertSame('weird_prop', $this->schema->map('weird_prop'));
    }

    public function test_type_for_returns_number_for_int_and_float(): void
    {
        $this->assertSame('number', $this->schema->typeFor('p', 1));
        $this->assertSame('number', $this->schema->typeFor('p', 1.23));
    }

    public function test_type_for_returns_boolean_for_bool(): void
    {
        $this->assertSame('boolean', $this->schema->typeFor('p', true));
        $this->assertSame('boolean', $this->schema->typeFor('p', false));
    }

    public function test_type_for_returns_string_for_string(): void
    {
        $this->assertSame('string', $this->schema->typeFor('p', 'ok'));
        $this->assertSame('string', $this->schema->typeFor('p', ''));
    }

    public function test_type_for_returns_null_for_unsupported_types(): void
    {
        $this->assertNull($this->schema->typeFor('p', ['array']));
        $this->assertNull($this->schema->typeFor('p', (object) ['a' => 1]));
        $this->assertNull($this->schema->typeFor('p', null));
    }

    public function test_compare_for_returns_delta_for_number(): void
    {
        $this->assertSame('delta', $this->schema->compareFor('totals.damage', 'number'));
    }

    public function test_compare_for_returns_flag_diff_for_boolean(): void
    {
        $this->assertSame('flag-diff', $this->schema->compareFor('flags.enabled', 'boolean'));
    }

    public function test_compare_for_returns_noop_for_others(): void
    {
        $this->assertSame('noop', $this->schema->compareFor('labels.name', 'string'));
        $this->assertSame('noop', $this->schema->compareFor('unknown.path', 'unknown'));
    }

    public function test_collections_returns_empty_array_by_default(): void
    {
        $this->assertSame([], $this->schema->collections());
    }
}
