<?php

namespace Tests\Unit\Game\Core\Items\Transformers;

use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftingItemPreviewTransformerTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private const EXPECTED_KEYS = [
        'item_id', 'inventory_slot_id', 'name', 'description', 'type',
        'base_damage', 'base_damage_mod', 'base_ac', 'base_ac_mod', 'base_healing', 'base_healing_mod',
        'str_modifier', 'dur_modifier', 'dex_modifier', 'chr_modifier', 'int_modifier', 'agi_modifier', 'focus_modifier',
        'ambush_chance', 'ambush_resistance_chance', 'counter_chance', 'counter_resistance_chance',
        'is_mythic', 'is_cosmic', 'is_unique', 'affix_count', 'holy_stacks', 'holy_stacks_applied',
        'socket_count', 'usable', 'holy_level', 'damages_kingdoms', 'item_prefix', 'item_suffix',
    ];

    public function test_plain_item_returns_exact_preview_contract_shape(): void
    {
        $item = $this->createItem([
            'type' => 'body',
            'base_ac' => 10,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $data = resolve(CraftingItemPreviewTransformer::class)->transform($item);

        $this->assertSame(self::EXPECTED_KEYS, array_keys($data));
        $this->assertSame($item->id, $data['item_id']);
        $this->assertNull($data['inventory_slot_id']);
        $this->assertNull($data['item_prefix']);
        $this->assertNull($data['item_suffix']);
        $this->assertFalse($data['is_mythic']);
        $this->assertFalse($data['is_cosmic']);
        $this->assertFalse($data['is_unique']);
        $this->assertIsInt($data['affix_count']);
        $this->assertSame(0, $data['affix_count']);
    }

    public function test_enchanted_item_returns_condensed_prefix_and_suffix_shape(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Sample Prefix', 'type' => 'prefix']);
        $suffix = $this->createItemAffix(['name' => 'Sample Suffix', 'type' => 'suffix']);

        $item = $this->createItem([
            'type' => 'body',
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);

        $data = resolve(CraftingItemPreviewTransformer::class)->transform($item);

        $this->assertSame(['id' => $prefix->id, 'name' => 'Sample Prefix'], $data['item_prefix']);
        $this->assertSame(['id' => $suffix->id, 'name' => 'Sample Suffix'], $data['item_suffix']);
        $this->assertSame(2, $data['affix_count']);
    }

    public function test_holy_item_reflects_holy_level_and_stack_fields(): void
    {
        $item = $this->createItem([
            'type' => 'body',
            'holy_level' => 3,
            'holy_stacks' => 5,
        ]);

        $data = resolve(CraftingItemPreviewTransformer::class)->transform($item);

        $this->assertSame(3, $data['holy_level']);
        $this->assertSame(5, $data['holy_stacks']);
        $this->assertIsInt($data['holy_stacks_applied']);
        $this->assertSame(0, $data['holy_stacks_applied']);
    }

    public function test_socketed_item_reflects_socket_count(): void
    {
        $item = $this->createItem([
            'type' => 'body',
            'socket_count' => 2,
        ]);

        $data = resolve(CraftingItemPreviewTransformer::class)->transform($item);

        $this->assertSame(2, $data['socket_count']);
    }

    public function test_inventory_slot_id_is_provided_when_passed_to_transform(): void
    {
        $item = $this->createItem(['type' => 'body']);

        $data = resolve(CraftingItemPreviewTransformer::class)->transform($item, 42);

        $this->assertSame(42, $data['inventory_slot_id']);
    }
}
