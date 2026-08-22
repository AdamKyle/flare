<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Validation\BatchCraftingRuleResolver;
use Tests\TestCase;

class BatchCraftingRuleResolverTest extends TestCase
{
    private ?BatchCraftingRuleResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = resolve(BatchCraftingRuleResolver::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resolver = null;
    }

    public function test_rules_delegates_to_the_craft_rule_builder(): void
    {
        $rules = $this->resolver->rules(BatchCraftingType::CRAFT, ['progress' => ['craft_mode' => 'specific_item']]);

        $this->assertArrayHasKey('progress.craft_mode', $rules);
        $this->assertArrayHasKey('progress.specific_item_id', $rules);
    }

    public function test_rules_delegates_to_the_craft_and_enchant_rule_builder(): void
    {
        $rules = $this->resolver->rules(BatchCraftingType::CRAFT_AND_ENCHANT, ['progress' => ['craft_enchant_mode' => 'amount']]);

        $this->assertArrayHasKey('progress.craft_enchant_mode', $rules);
        $this->assertArrayHasKey('progress.craft_amount', $rules);
    }

    public function test_rules_delegates_to_the_enchant_rule_builder(): void
    {
        $rules = $this->resolver->rules(BatchCraftingType::ENCHANT, ['progress' => ['enchant_mode' => 'event']]);

        $this->assertArrayHasKey('progress.enchant_mode', $rules);
    }

    public function test_rules_delegates_to_the_alchemy_rule_builder(): void
    {
        $rules = $this->resolver->rules(BatchCraftingType::ALCHEMY, ['progress' => ['alchemy_mode' => 'amount']]);

        $this->assertArrayHasKey('progress.alchemy_item_id', $rules);
    }

    public function test_rules_delegates_to_the_alchemy_rule_builder_for_experience_mode(): void
    {
        $rules = $this->resolver->rules(BatchCraftingType::ALCHEMY, ['progress' => ['alchemy_mode' => 'experience']]);

        $this->assertArrayHasKey('progress.alchemy_mode', $rules);
        $this->assertArrayNotHasKey('progress.alchemy_item_id', $rules);
    }

    public function test_rules_delegates_to_the_holy_oils_rule_builder(): void
    {
        $rules = $this->resolver->rules(BatchCraftingType::HOLY_OILS, ['progress' => ['holy_oils_mode' => 'selected_items']]);

        $this->assertArrayHasKey('progress.target_slot_ids', $rules);
        $this->assertArrayHasKey('progress.oil_slot_ids', $rules);
    }

    public function test_rules_delegates_to_the_holy_oils_rule_builder_for_inventory_set_mode(): void
    {
        $rules = $this->resolver->rules(BatchCraftingType::HOLY_OILS, ['progress' => ['holy_oils_mode' => 'inventory_set']]);

        $this->assertArrayHasKey('progress.inventory_set_id', $rules);
        $this->assertArrayNotHasKey('progress.target_slot_ids', $rules);
    }

    public function test_rules_delegates_to_the_trinketry_rule_builder(): void
    {
        $rules = $this->resolver->rules(BatchCraftingType::TRINKETRY, ['progress' => ['trinketry_mode' => 'experience']]);

        $this->assertArrayHasKey('progress.trinketry_mode', $rules);
        $this->assertArrayNotHasKey('progress.trinketry_item_id', $rules);
    }
}
