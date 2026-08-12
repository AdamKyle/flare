<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Transformers;

use App\Game\Character\CharacterInventory\Transformers\CharacterGemSlotsTransformer;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;

class CharacterGemSlotsTransformerTest extends TestCase
{
    use CreateGem, RefreshDatabase;

    private ?CharacterGemSlotsTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = resolve(CharacterGemSlotsTransformer::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->transformer = null;
    }

    public function test_transform_includes_the_gem_slot_amount_and_identity(): void
    {
        $gem = $this->createGem([
            'name' => 'Radiant Shard',
            'tier' => 2,
            'primary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.01,
            'secondary_atonement_amount' => 0.20,
            'tertiary_atonement_amount' => 0.10,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->gemBagManagement()->assignGemStackToBag($gem->id, 5)->getCharacter();

        $gemSlot = $character->gemBag->gemSlots()->where('gem_id', $gem->id)->first();

        $data = $this->transformer->transform($gemSlot);

        $this->assertSame($gemSlot->id, $data['slot_id']);
        $this->assertSame('Radiant Shard', $data['name']);
        $this->assertSame(2, $data['tier']);
        $this->assertSame(5, $data['amount']);
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

        $character = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->gemBagManagement()->assignGemStackToBag($gem->id, 1)->getCharacter();

        $gemSlot = $character->gemBag->gemSlots()->where('gem_id', $gem->id)->first();

        $data = $this->transformer->transform($gemSlot);

        $this->assertSame('Water', $data['element_atoned_to']);
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

        $character = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->gemBagManagement()->assignGemStackToBag($gem->id, 1)->getCharacter();

        $gemSlot = $character->gemBag->gemSlots()->where('gem_id', $gem->id)->first();

        $data = $this->transformer->transform($gemSlot);

        $this->assertSame('Fire', $data['element_atoned_to']);
    }
}
