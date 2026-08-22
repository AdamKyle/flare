<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Services\AlchemyAmountPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class AlchemyAmountPreviewServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?AlchemyAmountPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->character->update(['gold_dust' => 1000, 'shards' => 100]);
        $this->character = $this->character->refresh();

        $this->service = resolve(AlchemyAmountPreviewService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_build_returns_cost_and_availability_facts_for_an_affordable_item(): void
    {
        $item = $this->createItem([
            'gold_dust_cost' => 100,
            'shards_cost' => 10,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_item_id' => $item->id, 'alchemy_amount' => 3, 'listing_price' => null],
        ]);

        $this->assertSame($item->id, $result['item_id']);
        $this->assertSame(3, $result['requested_amount']);
        $this->assertSame(100, $result['gold_dust_cost_each']);
        $this->assertSame(10, $result['shards_cost_each']);
        $this->assertSame(300, $result['total_gold_dust_cost']);
        $this->assertSame(30, $result['total_shards_cost']);
        $this->assertSame(1000, $result['gold_dust_available']);
        $this->assertSame(100, $result['shards_available']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_build_reports_alchemy_bag_capacity_only_when_keeping(): void
    {
        $item = $this->createItem([
            'gold_dust_cost' => 100,
            'shards_cost' => 10,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $keptResult = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_item_id' => $item->id, 'alchemy_amount' => 1, 'listing_price' => null],
        ]);
        $destroyedResult = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['alchemy_item_id' => $item->id, 'alchemy_amount' => 1, 'listing_price' => null],
        ]);

        $this->assertSame(0, $keptResult['alchemy_bag_capacity']['current']);
        $this->assertSame($this->character->alchemy_bag_limit, $keptResult['alchemy_bag_capacity']['max']);
        $this->assertNull($destroyedResult['alchemy_bag_capacity']);
    }

    public function test_build_reports_a_blocker_when_gold_dust_is_insufficient(): void
    {
        $this->character->update(['gold_dust' => 0]);
        $item = $this->createItem([
            'gold_dust_cost' => 100,
            'shards_cost' => 0,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $result = $this->service->build($this->character->refresh(), [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_item_id' => $item->id, 'alchemy_amount' => 1, 'listing_price' => null],
        ]);

        $this->assertContains('You do not have enough Gold Dust to Alchemize this amount.', $result['blockers']);
    }

    public function test_build_reports_a_blocker_when_shards_are_insufficient(): void
    {
        $this->character->update(['shards' => 0]);
        $item = $this->createItem([
            'gold_dust_cost' => 0,
            'shards_cost' => 10,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $result = $this->service->build($this->character->refresh(), [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_item_id' => $item->id, 'alchemy_amount' => 1, 'listing_price' => null],
        ]);

        $this->assertContains('You do not have enough Shards to Alchemize this amount.', $result['blockers']);
    }

    public function test_build_reports_a_blocker_when_the_alchemy_bag_has_no_remaining_space(): void
    {
        $this->character->update(['alchemy_bag_limit' => 0]);
        $item = $this->createItem([
            'gold_dust_cost' => 0,
            'shards_cost' => 0,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $result = $this->service->build($this->character->refresh(), [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_item_id' => $item->id, 'alchemy_amount' => 1, 'listing_price' => null],
        ]);

        $this->assertContains('Your Alchemy Bag does not have enough remaining space.', $result['blockers']);
    }

    public function test_build_returns_the_unavailable_item_preview_when_the_item_no_longer_qualifies(): void
    {
        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_item_id' => 999999, 'alchemy_amount' => 1, 'listing_price' => null],
        ]);

        $this->assertNull($result['item_name']);
        $this->assertSame(0, $result['total_gold_dust_cost']);
        $this->assertContains('The selected item is no longer available to Alchemize.', $result['blockers']);
    }
}
