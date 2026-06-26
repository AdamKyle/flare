<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\Item;
use App\Flare\Transformers\UsableItemTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class UsableItemTransformerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function testTransformForItemReturnsGainAdditionalLevelTrueWhenGainsAdditionalLevelIsTrue(): void
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'gains_additional_level' => true,
        ]);

        $transformer = new UsableItemTransformer;
        $result = $transformer->transform($item);

        $this->assertTrue($result['gain_additional_level']);
    }

    public function testTransformForItemReturnsGainAdditionalLevelFalseWhenGainsAdditionalLevelIsFalse(): void
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'gains_additional_level' => false,
        ]);

        $transformer = new UsableItemTransformer;
        $result = $transformer->transform($item);

        $this->assertFalse($result['gain_additional_level']);
    }

    public function testTransformForSlotReturnsGainAdditionalLevelFromItem(): void
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'gains_additional_level' => true,
        ]);

        $character = (new \Tests\Setup\Character\CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $inventory = \App\Flare\Models\Inventory::where('character_id', $character->id)->first();
        $slot = \App\Flare\Models\InventorySlot::create([
            'inventory_id' => $inventory->id,
            'item_id' => $item->id,
        ]);

        $transformer = new UsableItemTransformer;
        $result = $transformer->transform($slot);

        $this->assertTrue($result['gain_additional_level']);
    }
}
