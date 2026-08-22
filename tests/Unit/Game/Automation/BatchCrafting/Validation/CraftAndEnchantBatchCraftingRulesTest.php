<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Validation\CraftAndEnchantBatchCraftingRules;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CraftAndEnchantBatchCraftingRulesTest extends TestCase
{
    private ?CraftAndEnchantBatchCraftingRules $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = resolve(CraftAndEnchantBatchCraftingRules::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->rules = null;
    }

    public function test_amount_rules_reject_neither_prefix_nor_suffix_selected(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 1,
                'craft_amount' => 5,
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('progress.specific_item_id', $validator->errors()->toArray());
    }

    public function test_amount_rules_pass_with_only_a_prefix_selected(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 1,
                'prefix_id' => 4,
                'craft_amount' => 5,
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertFalse($validator->fails());
    }

    public function test_amount_rules_accept_keep_with_an_inventory_destination(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 1,
                'prefix_id' => 4,
                'craft_amount' => 5,
                'output_destination' => 'inventory',
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertFalse($validator->fails());
    }

    public function test_amount_rules_accept_keep_with_a_crafted_items_set_destination(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 1,
                'prefix_id' => 4,
                'craft_amount' => 5,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertFalse($validator->fails());
    }

    public function test_amount_rules_reject_keep_with_a_normal_inventory_set_destination(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 1,
                'prefix_id' => 4,
                'craft_amount' => 5,
                'output_destination' => 'inventory_set',
                'output_set_id' => 1,
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('progress.output_destination', $validator->errors()->toArray());
    }

    public function test_amount_rules_reject_a_manually_submitted_output_set_id(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 1,
                'prefix_id' => 4,
                'craft_amount' => 5,
                'output_destination' => 'inventory',
                'output_set_id' => 1,
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('progress.output_set_id', $validator->errors()->toArray());
    }

    public function test_set_rules_accept_keep_with_a_normal_inventory_set_destination(): void
    {
        $requiredPositions = ['body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet', 'ring_0', 'ring_1', 'spell-damage', 'spell-healing'];
        $setPositions = array_fill_keys($requiredPositions, 1);
        $enchantments = array_fill_keys($requiredPositions, ['prefix_id' => 4, 'suffix_id' => null]);
        $requestData = [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'set',
                'set_positions' => $setPositions,
                'enchantments' => $enchantments,
                'output_destination' => 'inventory_set',
                'output_set_id' => 1,
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertFalse($validator->fails());
    }

    public function test_set_rules_reject_an_included_position_without_any_affix(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'set',
                'set_positions' => ['body' => 1],
                'enchantments' => ['body' => ['prefix_id' => null, 'suffix_id' => null]],
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('progress.enchantments', $validator->errors()->toArray());
    }

    public function test_set_rules_pass_when_every_included_position_has_an_affix(): void
    {
        $requiredPositions = ['body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet', 'ring_0', 'ring_1', 'spell-damage', 'spell-healing'];
        $setPositions = array_fill_keys($requiredPositions, 1);
        $enchantments = array_fill_keys($requiredPositions, ['prefix_id' => 4, 'suffix_id' => null]);
        $requestData = [
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'set',
                'set_positions' => $setPositions,
                'enchantments' => $enchantments,
            ],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertFalse($validator->fails());
    }

    public function test_experience_rules_allow_only_mode_and_listing_price_progress_keys(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'experience'],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertFalse($validator->fails());
    }

    public function test_experience_rules_reject_a_manually_supplied_item_id(): void
    {
        $requestData = [
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'specific_item_id' => 1],
        ];

        $validator = Validator::make($requestData, $this->rules->rules($requestData));

        $this->assertTrue($validator->fails());
    }
}
