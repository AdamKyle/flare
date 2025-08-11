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
        $this->schema = new class extends BaseManifest {
            // Intentionally no overrides; we want to exercise BaseManifest itself.
        };
    }

    protected function tearDown(): void
    {
        $this->schema = null;
        parent::tearDown();
    }

    public function testIncludesReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->schema->includes());
    }

    public function testExcludesReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->schema->excludes());
    }

    public function testMapReturnsOriginalPropByDefault(): void
    {
        $this->assertSame('total_damage', $this->schema->map('total_damage'));
        $this->assertSame('weird_prop', $this->schema->map('weird_prop'));
    }

    public function testTypeForReturnsNumberForIntAndFloat(): void
    {
        $this->assertSame('number', $this->schema->typeFor('p', 1));
        $this->assertSame('number', $this->schema->typeFor('p', 1.23));
    }

    public function testTypeForReturnsBooleanForBool(): void
    {
        $this->assertSame('boolean', $this->schema->typeFor('p', true));
        $this->assertSame('boolean', $this->schema->typeFor('p', false));
    }

    public function testTypeForReturnsStringForString(): void
    {
        $this->assertSame('string', $this->schema->typeFor('p', 'ok'));
        $this->assertSame('string', $this->schema->typeFor('p', ''));
    }

    public function testTypeForReturnsNullForUnsupportedTypes(): void
    {
        $this->assertNull($this->schema->typeFor('p', ['array']));
        $this->assertNull($this->schema->typeFor('p', (object)['a' => 1]));
        $this->assertNull($this->schema->typeFor('p', null));
    }

    public function testCompareForReturnsDeltaForNumber(): void
    {
        $this->assertSame('delta', $this->schema->compareFor('totals.damage', 'number'));
    }

    public function testCompareForReturnsFlagDiffForBoolean(): void
    {
        $this->assertSame('flag-diff', $this->schema->compareFor('flags.enabled', 'boolean'));
    }

    public function testCompareForReturnsNoopForOthers(): void
    {
        $this->assertSame('noop', $this->schema->compareFor('labels.name', 'string'));
        $this->assertSame('noop', $this->schema->compareFor('unknown.path', 'unknown'));
    }

    public function testCollectionsReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->schema->collections());
    }
}
