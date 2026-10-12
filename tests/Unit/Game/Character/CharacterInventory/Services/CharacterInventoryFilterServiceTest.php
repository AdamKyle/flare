<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterInventoryFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateHolyStack;
use Tests\Traits\CreateItemAffix;

class CharacterInventoryFilterServiceTest extends TestCase
{
    use CreateHolyStack, CreateItemAffix, RefreshDatabase;

    private CharacterFactory $characterFactory;

    private CharacterInventoryFilterService $filterService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter();
        $this->filterService = new CharacterInventoryFilterService;
    }

    public function test_no_filters_return_all_slots(): void
    {
        $weapon = $this->characterFactory->createItem(['type' => 'sword']);
        $armour = $this->characterFactory->createItem(['type' => 'body']);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($weapon)
            ->giveItem($armour)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter($inventory->slots()->with('item')->get(), []);

        $this->assertCount(2, $filtered);
    }

    public function test_item_type_filter_returns_matching_slots(): void
    {
        $weapon = $this->characterFactory->createItem(['type' => 'sword']);
        $armour = $this->characterFactory->createItem(['type' => 'body']);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($weapon)
            ->giveItem($armour)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter(
            $inventory->slots()->with('item')->get(),
            ['item_types' => ['sword']],
        );

        $this->assertSame([$weapon->id], $filtered->pluck('item_id')->all());
    }

    public function test_multiple_item_types_return_each_matching_slot(): void
    {
        $weapon = $this->characterFactory->createItem(['type' => 'sword']);
        $armour = $this->characterFactory->createItem(['type' => 'body']);
        $ring = $this->characterFactory->createItem(['type' => 'ring']);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($weapon)
            ->giveItem($armour)
            ->giveItem($ring)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter(
            $inventory->slots()->with('item')->get(),
            ['item_types' => ['sword', 'ring']],
        );

        $this->assertSame([$weapon->id, $ring->id], $filtered->pluck('item_id')->all());
    }

    public function test_cosmic_filter_returns_cosmic_items(): void
    {
        $cosmic = $this->characterFactory->createItem(['is_cosmic' => true]);
        $normal = $this->characterFactory->createItem(['is_cosmic' => false]);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($cosmic)
            ->giveItem($normal)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter(
            $inventory->slots()->with('item')->get(),
            ['rarity' => 'cosmic'],
        );

        $this->assertSame([$cosmic->id], $filtered->pluck('item_id')->all());
    }

    public function test_mythic_filter_excludes_cosmic_items(): void
    {
        $mythic = $this->characterFactory->createItem(['is_mythic' => true, 'is_cosmic' => false]);
        $cosmicMythic = $this->characterFactory->createItem(['is_mythic' => true, 'is_cosmic' => true]);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($mythic)
            ->giveItem($cosmicMythic)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter(
            $inventory->slots()->with('item')->get(),
            ['rarity' => 'mythic'],
        );

        $this->assertSame([$mythic->id], $filtered->pluck('item_id')->all());
    }

    public function test_unique_filter_excludes_mythic_and_cosmic_items(): void
    {
        $uniqueAffix = $this->createItemAffix(['randomly_generated' => true]);
        $mythicUniqueAffix = $this->createItemAffix(['randomly_generated' => true]);
        $unique = $this->characterFactory->createItem([
            'item_prefix_id' => $uniqueAffix->id,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $mythicUnique = $this->characterFactory->createItem([
            'item_prefix_id' => $mythicUniqueAffix->id,
            'is_mythic' => true,
            'is_cosmic' => false,
        ]);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($unique)
            ->giveItem($mythicUnique)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter(
            $inventory->slots()->with('item')->get(),
            ['rarity' => 'unique'],
        );

        $this->assertSame([$unique->id], $filtered->pluck('item_id')->all());
    }

    public function test_single_enchanted_filter_excludes_special_and_holy_items(): void
    {
        $singleAffix = $this->createItemAffix();
        $holyAffix = $this->createItemAffix();
        $single = $this->characterFactory->createItem([
            'item_prefix_id' => $singleAffix->id,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $holy = $this->characterFactory->createItem([
            'item_prefix_id' => $holyAffix->id,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $this->createHolyStack(['item_id' => $holy->id]);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($single)
            ->giveItem($holy)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter(
            $inventory->slots()->with('item')->get(),
            ['rarity' => 'single-enchanted'],
        );

        $this->assertSame([$single->id], $filtered->pluck('item_id')->all());
    }

    public function test_dual_enchanted_filter_excludes_special_and_holy_items(): void
    {
        $dualPrefix = $this->createItemAffix();
        $dualSuffix = $this->createItemAffix();
        $uniquePrefix = $this->createItemAffix(['randomly_generated' => true]);
        $uniqueSuffix = $this->createItemAffix();
        $dual = $this->characterFactory->createItem([
            'item_prefix_id' => $dualPrefix->id,
            'item_suffix_id' => $dualSuffix->id,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $unique = $this->characterFactory->createItem([
            'item_prefix_id' => $uniquePrefix->id,
            'item_suffix_id' => $uniqueSuffix->id,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($dual)
            ->giveItem($unique)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter(
            $inventory->slots()->with('item')->get(),
            ['rarity' => 'dual-enchanted'],
        );

        $this->assertSame([$dual->id], $filtered->pluck('item_id')->all());
    }

    public function test_invalid_rarity_does_not_filter_slots(): void
    {
        $item = $this->characterFactory->createItem(['type' => 'sword']);
        $inventory = $this->characterFactory->inventoryManagement()
            ->giveItem($item)
            ->getCharacter()
            ->inventory;

        $filtered = $this->filterService->filter(
            $inventory->slots()->with('item')->get(),
            ['rarity' => 'unknown'],
        );

        $this->assertSame([$item->id], $filtered->pluck('item_id')->all());
    }
}
