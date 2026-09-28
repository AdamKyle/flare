<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Transformers;

use App\Game\Character\CharacterInventory\Transformers\CharacterGemsTransformer;
use App\Game\Gems\Transformers\CharacterGemTransformer;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGem;

class CharacterGemsTransformerTest extends TestCase
{
    use CreateCharacterGemModifier, CreateGem, RefreshDatabase;

    public function test_transform_returns_generic_ordered_modifier_payload(): void
    {
        $gem = $this->createGem(['name' => 'Radiant Shard', 'tier' => 2]);
        $this->createCharacterGemModifier([
            'gem_id' => $gem->id,
            'roll_position' => 2,
            'modifier_type' => CharacterGemModifierType::BASE_DAMAGE_MOD,
            'amount' => 0.05,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $gem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::STRENGTH,
            'amount' => 40,
        ]);

        $data = (new CharacterGemsTransformer(new CharacterGemTransformer))->transform($gem->refresh());

        $this->assertSame($gem->id, $data['id']);
        $this->assertSame('Radiant Shard', $data['name']);
        $this->assertSame(2, $data['tier']);
        $this->assertSame('character', $data['domain']);
        $this->assertSame('strength', $data['modifiers'][0]['modifier_type']);
        $this->assertSame(40.0, $data['modifiers'][0]['amount']);
        $this->assertSame('base_damage_mod', $data['modifiers'][1]['modifier_type']);
        $this->assertArrayNotHasKey('label', $data['modifiers'][0]);
        $this->assertArrayNotHasKey('display_type', $data['modifiers'][0]);
        $this->assertArrayNotHasKey('primary_atonement_amount', $data);
        $this->assertArrayNotHasKey('weak_against', $data);
    }
}
