<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Setup;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Setup\AlchemyBatchCraftingSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class AlchemyBatchCraftingSetupServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?AlchemyBatchCraftingSetupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(AlchemyBatchCraftingSetupService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_supports_only_alchemy(): void
    {
        $this->assertTrue($this->service->supports(BatchCraftingType::ALCHEMY));
        $this->assertFalse($this->service->supports(BatchCraftingType::TRINKETRY));
    }

    public function test_preview_is_null_for_experience_mode(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $preview = $this->service->preview($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $this->assertNull($preview);
    }

    public function test_resolve_start_builds_amount_progress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $item = $this->createItem([
            'gold_dust_cost' => 10,
            'shards_cost' => 5,
            'skill_level_required' => 1,
            'skill_level_trivial' => 0,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $item->id, 'alchemy_amount' => 10],
        ]);

        $this->assertSame('amount', $result['progress']['alchemy_mode']);
        $this->assertSame($item->id, $result['progress']['alchemy_item_id']);
        $this->assertSame(10, $result['progress']['alchemy_amount']);
        $this->assertSame(0, $result['progress']['completed_amount']);
        $this->assertArrayNotHasKey('listing_price', $result['progress']);
    }

    public function test_resolve_start_includes_listing_price_only_when_listing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $item = $this->createItem([
            'gold_dust_cost' => 10,
            'shards_cost' => 5,
            'skill_level_required' => 1,
            'skill_level_trivial' => 0,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $item->id, 'alchemy_amount' => 10, 'listing_price' => 500],
        ]);

        $this->assertSame(500, $result['progress']['listing_price']);
    }

    public function test_resolve_start_reports_a_blocker_for_experience_without_a_meaningful_item(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience'],
        ]);

        $this->assertNotEmpty($result['blockers']);
    }
}
