<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Enums\EnchantingBatchMode;
use App\Game\Automation\BatchCrafting\Enums\HolyOilsBatchMode;
use App\Game\Automation\BatchCrafting\Enums\TrinketryBatchMode;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingModeService;
use Tests\TestCase;

class BatchCraftingModeServiceTest extends TestCase
{
    private ?BatchCraftingModeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(BatchCraftingModeService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_resolve_returns_the_typed_mode_enum_for_every_batch_type(): void
    {
        $this->assertSame(CraftingBatchMode::AMOUNT, $this->service->resolve(BatchCraftingType::CRAFT, 'specific_item'));
        $this->assertSame(CraftAndEnchantBatchMode::AMOUNT, $this->service->resolve(BatchCraftingType::CRAFT_AND_ENCHANT, 'amount'));
        $this->assertSame(EnchantingBatchMode::EVENT, $this->service->resolve(BatchCraftingType::ENCHANT, 'event'));
        $this->assertSame(AlchemyBatchMode::AMOUNT, $this->service->resolve(BatchCraftingType::ALCHEMY, 'amount'));
        $this->assertSame(HolyOilsBatchMode::SELECTED_ITEMS, $this->service->resolve(BatchCraftingType::HOLY_OILS, 'selected_items'));
        $this->assertSame(TrinketryBatchMode::EXPERIENCE, $this->service->resolve(BatchCraftingType::TRINKETRY, 'experience'));
    }

    public function test_execution_window_size_delegates_to_the_resolved_mode(): void
    {
        $this->assertSame(23, $this->service->executionWindowSize(BatchCraftingType::CRAFT_AND_ENCHANT, 'experience'));
        $this->assertNull($this->service->executionWindowSize(BatchCraftingType::ALCHEMY, 'amount'));
    }

    public function test_allowed_dispositions_delegates_to_the_resolved_mode(): void
    {
        $dispositions = $this->service->allowedDispositions(BatchCraftingType::TRINKETRY, 'experience');

        $this->assertSame(TrinketryBatchMode::EXPERIENCE->allowedDispositions(), $dispositions);
    }

    public function test_allowed_output_destinations_delegates_to_the_resolved_mode(): void
    {
        $destinations = $this->service->allowedOutputDestinations(BatchCraftingType::CRAFT_AND_ENCHANT, 'amount');

        $this->assertSame(CraftAndEnchantBatchMode::AMOUNT->allowedOutputDestinations(), $destinations);
    }
}
