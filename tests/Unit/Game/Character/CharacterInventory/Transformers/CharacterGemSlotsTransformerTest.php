<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Transformers;

use App\Game\Character\CharacterInventory\Transformers\CharacterGemSlotsTransformer;
use App\Game\Gems\Transformers\CharacterGemTransformer;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGem;

class CharacterGemSlotsTransformerTest extends TestCase
{
    use CreateCharacterGemModifier, CreateGem, RefreshDatabase;

    public function test_transform_includes_slot_data_and_generic_modifiers(): void
    {
        $gem = $this->createGem(['name' => 'Radiant Shard', 'tier' => 2]);
        $this->createCharacterGemModifier([
            'gem_id' => $gem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::FOCUS,
            'amount' => 50,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->gemBagManagement()->assignGemStackToBag($gem->id, 5)->getCharacter();
        $gemSlot = $character->gemBag->gemSlots()->where('gem_id', $gem->id)->first();

        $data = (new CharacterGemSlotsTransformer(new CharacterGemTransformer))->transform($gemSlot);

        $this->assertSame($gemSlot->id, $data['slot_id']);
        $this->assertSame(5, $data['amount']);
        $this->assertSame('Radiant Shard', $data['name']);
        $this->assertSame('focus', $data['modifiers'][0]['modifier_type']);
        $this->assertSame(50.0, $data['modifiers'][0]['amount']);
    }
}
