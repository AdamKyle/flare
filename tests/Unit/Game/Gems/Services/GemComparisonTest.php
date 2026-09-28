<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Game\Gems\Services\GemComparison;
use App\Game\Gems\Transformers\CharacterGemTransformer;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class GemComparisonTest extends TestCase
{
    use CreateCharacterGemModifier, CreateGem, CreateItem, RefreshDatabase;

    public function test_compare_returns_generic_removed_and_added_gems(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter();
        $item = $this->createItem(['socket_count' => 2]);
        $removedGem = $this->createGem(['name' => 'Old Gem', 'tier' => 2]);
        $addedGem = $this->createGem(['name' => 'New Gem', 'tier' => 4]);
        $this->createCharacterGemModifier([
            'gem_id' => $removedGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::STRENGTH,
            'amount' => 30,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $addedGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::FIRE_PENETRATION,
            'amount' => 0.08,
        ]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $removedGem->id]);
        $character = $characterFactory->inventoryManagement()->giveItem($item)->getCharacter();
        $gemSlot = $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $addedGem->id,
            'amount' => 1,
        ]);

        $result = (new GemComparison(new CharacterGemTransformer))->compareGemForItem(
            $character->refresh(),
            $character->inventory->slots->first()->id,
            $gemSlot->id,
        );

        $this->assertSame('Old Gem', $result['attached_gems'][0]['name']);
        $this->assertSame('New Gem', $result['added_gem']['name']);
        $this->assertSame('strength', $result['replacements'][0]['removed_gem']['modifiers'][0]['modifier_type']);
        $this->assertSame('fire_penetration', $result['replacements'][0]['added_gem']['modifiers'][0]['modifier_type']);
    }

    public function test_compare_rejects_an_item_not_owned_by_character(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $result = (new GemComparison(new CharacterGemTransformer))->compareGemForItem($character, 999999, 999999);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Selected item was not found in your inventory.', $result['message']);
    }
}
