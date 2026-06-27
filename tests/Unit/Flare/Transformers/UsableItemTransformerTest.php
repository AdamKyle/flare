<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Transformers\UsableItemTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class UsableItemTransformerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_transform_for_item_returns_gain_additional_level_true_when_gains_additional_level_is_true(): void
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

    public function test_transform_for_item_returns_gain_additional_level_false_when_gains_additional_level_is_false(): void
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

    public function test_transform_for_slot_returns_gain_additional_level_from_item(): void
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'gains_additional_level' => true,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $inventory = Inventory::where('character_id', $character->id)->first();
        $slot = InventorySlot::create([
            'inventory_id' => $inventory->id,
            'item_id' => $item->id,
        ]);

        $transformer = new UsableItemTransformer;
        $result = $transformer->transform($slot);

        $this->assertTrue($result['gain_additional_level']);
    }
}
