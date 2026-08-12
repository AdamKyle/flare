<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Transformers;

use App\Game\Character\CharacterInventory\Transformers\CharacterGemsTransformer;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGem;

class CharacterGemsTransformerTest extends TestCase
{
    use CreateGem, RefreshDatabase;

    private ?CharacterGemsTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = resolve(CharacterGemsTransformer::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->transformer = null;
    }

    public function test_transform_identifies_the_highest_atonement_as_the_element_atoned_to(): void
    {
        $gem = $this->createGem([
            'primary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.01,
            'secondary_atonement_amount' => 0.20,
            'tertiary_atonement_amount' => 0.10,
        ]);

        $data = $this->transformer->transform($gem);

        $this->assertSame('Water', $data['element_atoned_to']);
        $this->assertSame(0.20, $data['element_atoned_to_amount']);
    }

    public function test_transform_calculates_weak_and_strong_matchups_for_the_highest_atonement(): void
    {
        $gem = $this->createGem([
            'primary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.01,
            'secondary_atonement_amount' => 0.20,
            'tertiary_atonement_amount' => 0.10,
        ]);

        $data = $this->transformer->transform($gem);

        $this->assertSame('Ice', $data['weak_against']);
        $this->assertSame('Fire', $data['strong_against']);
    }

    public function test_transform_identifies_the_tertiary_atonement_as_the_highest_when_it_is_greatest(): void
    {
        $gem = $this->createGem([
            'primary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.01,
            'secondary_atonement_amount' => 0.10,
            'tertiary_atonement_amount' => 0.20,
        ]);

        $data = $this->transformer->transform($gem);

        $this->assertSame('Fire', $data['element_atoned_to']);
        $this->assertSame(0.20, $data['element_atoned_to_amount']);
    }

    public function test_transform_includes_gem_identity_and_atonement_fields(): void
    {
        $gem = $this->createGem([
            'name' => 'Radiant Shard',
            'tier' => 3,
        ]);

        $data = $this->transformer->transform($gem);

        $this->assertSame($gem->id, $data['id']);
        $this->assertSame('Radiant Shard', $data['name']);
        $this->assertSame(3, $data['tier']);
        $this->assertSame($gem->primary_atonement_amount, $data['primary_atonement_amount']);
        $this->assertSame($gem->secondary_atonement_amount, $data['secondary_atonement_amount']);
        $this->assertSame($gem->tertiary_atonement_amount, $data['tertiary_atonement_amount']);
    }
}
