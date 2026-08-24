<?php

namespace Tests\Unit\Game\Core\Items\Values;

use App\Game\Core\Items\Values\ItemUniqueness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemUniquenessTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    public function test_is_unique_true_when_suffix_is_randomly_generated(): void
    {
        $suffix = $this->createItemAffix(['type' => 'suffix', 'randomly_generated' => true]);
        $item = $this->createItem(['item_suffix_id' => $suffix->id, 'item_prefix_id' => null]);

        $this->assertTrue(ItemUniqueness::fromItem($item)->isUnique());
    }

    public function test_is_unique_false_when_suffix_is_not_randomly_generated(): void
    {
        $suffix = $this->createItemAffix(['type' => 'suffix', 'randomly_generated' => false]);
        $item = $this->createItem(['item_suffix_id' => $suffix->id, 'item_prefix_id' => null]);

        $this->assertFalse(ItemUniqueness::fromItem($item)->isUnique());
    }

    public function test_is_unique_true_when_no_suffix_and_prefix_is_randomly_generated(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);
        $item = $this->createItem(['item_suffix_id' => null, 'item_prefix_id' => $prefix->id]);

        $this->assertTrue(ItemUniqueness::fromItem($item)->isUnique());
    }

    public function test_is_unique_false_when_no_suffix_and_prefix_is_not_randomly_generated(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false]);
        $item = $this->createItem(['item_suffix_id' => null, 'item_prefix_id' => $prefix->id]);

        $this->assertFalse(ItemUniqueness::fromItem($item)->isUnique());
    }

    public function test_is_unique_false_when_no_suffix_and_no_prefix(): void
    {
        $item = $this->createItem(['item_suffix_id' => null, 'item_prefix_id' => null]);

        $this->assertFalse(ItemUniqueness::fromItem($item)->isUnique());
    }

    public function test_suffix_takes_precedence_over_prefix(): void
    {
        $suffix = $this->createItemAffix(['type' => 'suffix', 'randomly_generated' => true]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false]);
        $item = $this->createItem(['item_suffix_id' => $suffix->id, 'item_prefix_id' => $prefix->id]);

        $this->assertTrue(ItemUniqueness::fromItem($item)->isUnique());
    }
}
