<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantAmountPreviewService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantAmountPreviewServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?CraftAndEnchantAmountPreviewService $service;

    private ?Item $item;

    private ?ItemAffix $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);

        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->character = $this->character->refresh();

        $this->item = $this->createItem(['name' => 'Preview Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 50]);

        $this->service = resolve(CraftAndEnchantAmountPreviewService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
        $this->item = null;
        $this->prefix = null;
    }

    public function test_build_combines_crafting_and_enchanting_gold_costs(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 5,
                'output_destination' => 'inventory',
            ],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertSame($this->item->id, $result['item_id']);
        $this->assertSame(10, $result['crafting_gold_cost_each']);
        $this->assertSame(50, $result['enchanting_gold_cost_each']);
        $this->assertSame(60, $result['total_gold_cost_each']);
        $this->assertSame(300, $result['total_requested_gold_cost']);
        $this->assertSame($this->prefix->id, $result['prefix']['id']);
        $this->assertNull($result['suffix']);
        $this->assertSame([], $result['blockers']);
    }

    public function test_build_flags_unavailable_affix_as_a_blocker(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => 999999,
                'suffix_id' => null,
                'craft_amount' => 5,
                'output_destination' => 'inventory',
            ],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertContains('The selected enchantment is no longer available.', $result['blockers']);
    }

    public function test_build_flags_insufficient_gold(): void
    {
        $this->character->update(['gold' => 0]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 5,
                'output_destination' => 'inventory',
            ],
        ];

        $result = $this->service->build($this->character->refresh(), $validated);

        $this->assertContains('You do not have enough Gold to craft and enchant this item.', $result['blockers']);
    }

    public function test_build_returns_unavailable_item_preview_when_item_no_longer_craftable(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 999999,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 5,
                'output_destination' => 'inventory',
            ],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertNull($result['item_id']);
        $this->assertNotEmpty($result['blockers']);
    }

    public function test_build_returns_destination_capacity_for_keep(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 5,
                'output_destination' => 'inventory',
            ],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertNotNull($result['destination_capacity']);
        $this->assertSame(30, $result['destination_capacity']['max']);
    }

    public function test_build_flags_insufficient_destination_space(): void
    {
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertContains('The selected destination does not have enough remaining space.', $result['blockers']);
    }

    public function test_build_has_no_destination_capacity_for_non_keep_dispositions(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 5,
                'output_destination' => 'inventory',
            ],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertNull($result['destination_capacity']);
    }

    public function test_build_creates_the_crafted_items_set_for_a_character_with_no_existing_special_set(): void
    {
        $this->assertNull(resolve(BatchCraftingSetService::class)->findBatchCraftingSet($this->character));

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertNotNull($result['output_set_id']);
        $this->assertNotNull($result['destination_capacity']);
        $this->assertNotNull(resolve(BatchCraftingSetService::class)->findBatchCraftingSet($this->character));
    }
}
