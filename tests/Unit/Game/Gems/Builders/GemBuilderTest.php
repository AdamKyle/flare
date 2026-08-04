<?php

namespace Tests\Unit\Game\Gems\Builders;

use App\Flare\Models\Gem;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\CreateGem;

class GemBuilderTest extends TestCase
{
    use CreateGem, RefreshDatabase;

    public function test_creates_a_new_gem_when_no_matching_gem_exists(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->andReturn(5, 10, 15, 3);
            })
        );

        $gem = resolve(GemBuilder::class)->buildGem(1);

        $this->assertSame('Glinting Bytocchacuaite', $gem->name);
        $this->assertSame(Gem::DOMAIN_CHARACTER, $gem->domain);
        $this->assertSame(1, $gem->tier);
        $this->assertSame(GemTypeValue::FIRE, $gem->primary_atonement_type);
        $this->assertSame(GemTypeValue::WATER, $gem->secondary_atonement_type);
        $this->assertSame(GemTypeValue::ICE, $gem->tertiary_atonement_type);
        $this->assertEquals(0.05, $gem->primary_atonement_amount);
        $this->assertEquals(0.1, $gem->secondary_atonement_amount);
        $this->assertEquals(0.15, $gem->tertiary_atonement_amount);
    }

    public function test_reuses_matching_character_domain_gem(): void
    {
        $existingGem = $this->createGem([
            'name' => 'Glinting Bytocchacuaite',
            'domain' => Gem::DOMAIN_CHARACTER,
            'tier' => 1,
            'primary_atonement_type' => GemTypeValue::FIRE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::ICE,
            'primary_atonement_amount' => 0.05,
            'secondary_atonement_amount' => 0.1,
            'tertiary_atonement_amount' => 0.15,
        ]);

        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->andReturn(5, 10, 15, 3);
            })
        );

        $gem = resolve(GemBuilder::class)->buildGem(1);

        $this->assertSame($existingGem->id, $gem->id);
        $this->assertSame(1, Gem::count());
    }

    public function test_does_not_reuse_matching_map_domain_gem_for_character_gem(): void
    {
        $this->createGem([
            'name' => 'Glinting Bytocchacuaite',
            'domain' => Gem::DOMAIN_MAP,
            'tier' => 1,
            'primary_atonement_type' => GemTypeValue::FIRE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::ICE,
            'primary_atonement_amount' => 0.05,
            'secondary_atonement_amount' => 0.1,
            'tertiary_atonement_amount' => 0.15,
        ]);

        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->andReturn(5, 10, 15, 3);
            })
        );

        $gem = resolve(GemBuilder::class)->buildGem(1);

        $this->assertSame(Gem::DOMAIN_CHARACTER, $gem->domain);
        $this->assertSame(2, Gem::count());
    }
}
