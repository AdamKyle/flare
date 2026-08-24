<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantBatchAttemptService;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantBatchAttemptServiceTest extends TestCase
{
    use CreateGameSkill, CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?Item $item;

    private ?ItemAffix $prefix;

    private ?CraftAndEnchantBatchAttemptService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);

        $this->character->update(['gold' => 100000, 'inventory_max' => 30]);
        $this->character = $this->character->refresh();

        $this->item = $this->createItem(['name' => 'Attempt Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $this->service = resolve(CraftAndEnchantBatchAttemptService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->item = null;
        $this->prefix = null;
        $this->service = null;
    }

    public function test_craft_and_enchant_returns_a_finished_item_on_success(): void
    {
        $result = $this->service->craftAndEnchant($this->character, $this->item, 'weapon', $this->prefix->id, null);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['item']);
        $this->assertGreaterThan(0, $result['gold_cost']);
    }

    public function test_craft_and_enchant_translates_a_failed_craft_roll(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $item = $this->createItem(['name' => 'Failing Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $service = resolve(CraftAndEnchantBatchAttemptService::class);
        $result = $service->craftAndEnchant($this->character, $item, 'weapon', $this->prefix->id, null);

        $this->assertFalse($result['success']);
        $this->assertNull($result['item']);
        $this->assertSame(BatchCraftingActionStatus::FAILED, $result['result']->actionStatus());
    }

    public function test_craft_and_enchant_fails_when_the_requested_affix_is_invalid(): void
    {
        $result = $this->service->craftAndEnchant($this->character, $this->item, 'weapon', 999999, null);

        $this->assertFalse($result['success']);
        $this->assertNull($result['item']);
        $this->assertFalse($result['result']->didCraft());
    }

    public function test_craft_and_enchant_fails_when_the_enchant_roll_fails(): void
    {
        $item = $this->createItem(['name' => 'Trivial Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => -10]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $service = resolve(CraftAndEnchantBatchAttemptService::class);
        $result = $service->craftAndEnchant($this->character, $item, 'weapon', $prefix->id, null);

        $this->assertFalse($result['success']);
        $this->assertNull($result['item']);
        $this->assertFalse($result['result']->didCraft());
    }

    public function test_attempt_applies_the_disposition_after_a_successful_craft_and_enchant(): void
    {
        $result = $this->service->attempt($this->character, BatchCraftingDisposition::DESTROY, $this->item, 'weapon', $this->prefix->id, null, null, null);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
    }

    public function test_attempt_returns_the_craft_failure_result_without_applying_a_disposition(): void
    {
        $result = $this->service->attempt($this->character, BatchCraftingDisposition::DESTROY, $this->item, 'weapon', 999999, null, null, null);

        $this->assertFalse($result->didCraft());
    }

    public function test_apply_disposition_keep_fails_when_placement_returns_no_destination(): void
    {
        $result = $this->service->applyDisposition($this->character, BatchCraftingDisposition::KEEP, $this->item, fn (Item $item) => null, null, 10);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertSame(10, $result->goldSpent());
    }

    public function test_apply_disposition_keep_places_the_item_in_an_inventory_set(): void
    {
        $targetSet = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false]);
        $craftingBatchAttemptService = resolve(CraftingBatchAttemptService::class);
        $placeItem = $craftingBatchAttemptService->resolveRetainedDestination($this->character, BatchCraftingOutputDestination::INVENTORY_SET->value, $targetSet->id);

        $result = $this->service->applyDisposition($this->character, BatchCraftingDisposition::KEEP, $this->item, $placeItem, null, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $targetSet->slots()->where('item_id', $this->item->id)->count());
    }

    public function test_apply_disposition_sell_credits_gold_and_returns_sold_result(): void
    {
        $goldBefore = $this->character->gold;

        $result = $this->service->applyDisposition($this->character, BatchCraftingDisposition::SELL, $this->item, null, null, 10);

        $this->assertSame(BatchCraftingActionStatus::SOLD, $result->actionStatus());
        $this->assertGreaterThan($goldBefore, $this->character->refresh()->gold);
    }

    public function test_apply_disposition_destroy_returns_destroyed_result(): void
    {
        $result = $this->service->applyDisposition($this->character, BatchCraftingDisposition::DESTROY, $this->item, null, null, 10);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
    }

    public function test_apply_disposition_list_returns_listed_result(): void
    {
        $result = $this->service->applyDisposition($this->character, BatchCraftingDisposition::LIST, $this->item, null, 250, 10);

        $this->assertSame(BatchCraftingActionStatus::LISTED, $result->actionStatus());
    }

    public function test_apply_disposition_disenchant_returns_disenchanted_result(): void
    {
        $result = $this->service->applyDisposition($this->character, BatchCraftingDisposition::DISENCHANT, $this->item, null, null, 10);

        $this->assertSame(BatchCraftingActionStatus::DISENCHANTED, $result->actionStatus());
    }

    public function test_sell_for_displacement_credits_gold_and_returns_the_amount_gained(): void
    {
        $goldBefore = $this->character->gold;

        $goldGained = $this->service->sellForDisplacement($this->character, $this->item);

        $this->assertSame($goldBefore + $goldGained, $this->character->refresh()->gold);
    }

    public function test_destroy_for_displacement_does_not_change_gold(): void
    {
        $goldBefore = $this->character->gold;

        $this->service->destroyForDisplacement($this->character, $this->item);

        $this->assertSame($goldBefore, $this->character->refresh()->gold);
    }
}
