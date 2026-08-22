<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Setup;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Setup\CraftAndEnchantBatchCraftingSetupService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantBatchCraftingSetupServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CraftAndEnchantBatchCraftingSetupService $service;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false)
            ->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);

        $this->character->update(['gold' => 10000, 'inventory_max' => 30]);
        $this->character = $this->character->refresh();

        $this->service = resolve(CraftAndEnchantBatchCraftingSetupService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
        $this->character = null;
    }

    public function test_supports_only_craft_and_enchant(): void
    {
        $this->assertTrue($this->service->supports(BatchCraftingType::CRAFT_AND_ENCHANT));
        $this->assertFalse($this->service->supports(BatchCraftingType::CRAFT));
    }

    public function test_resolve_start_for_amount_builds_expected_progress(): void
    {
        $item = $this->createItem(['name' => 'Setup Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'prefix_id' => $prefix->id,
                'suffix_id' => null,
                'craft_amount' => 3,
            ],
        ];

        $result = $this->service->resolveStart($this->character, $validated);

        $this->assertSame([], $result['blockers']);
        $this->assertSame('amount', $result['progress']['craft_enchant_mode']);
        $this->assertSame($item->id, $result['progress']['specific_item_id']);
        $this->assertSame($prefix->id, $result['progress']['prefix_id']);
        $this->assertSame(0, $result['progress']['completed_amount']);
        $this->assertNull($result['progress']['current_item_id']);
    }

    public function test_resolve_start_for_amount_reports_blocker_when_prerequisites_missing(): void
    {
        $characterWithoutSkills = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['name' => 'Setup Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'prefix_id' => $prefix->id,
                'suffix_id' => null,
                'craft_amount' => 3,
            ],
        ];

        $result = $this->service->resolveStart($characterWithoutSkills, $validated);

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_resolve_start_for_amount_with_inventory_destination_has_no_output_set_id(): void
    {
        $item = $this->createItem(['name' => 'Setup Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'prefix_id' => $prefix->id,
                'suffix_id' => null,
                'craft_amount' => 3,
                'output_destination' => 'inventory',
            ],
        ];

        $result = $this->service->resolveStart($this->character, $validated);

        $this->assertSame([], $result['blockers']);
        $this->assertSame('inventory', $result['progress']['output_destination']);
        $this->assertNull($result['progress']['output_set_id']);
    }

    public function test_resolve_start_for_amount_with_crafted_items_set_destination_resolves_the_destination(): void
    {
        $item = $this->createItem(['name' => 'Setup Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'prefix_id' => $prefix->id,
                'suffix_id' => null,
                'craft_amount' => 3,
                'output_destination' => 'crafted_items_set',
            ],
        ];

        $result = $this->service->resolveStart($this->character, $validated);

        $this->assertSame([], $result['blockers']);
        $this->assertSame('crafted_items_set', $result['progress']['output_destination']);
        $this->assertNull($result['progress']['output_set_id']);
    }

    public function test_resolve_start_for_experience_builds_expected_progress(): void
    {
        $this->createItem(['name' => 'Setup Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience'],
        ];

        $result = $this->service->resolveStart($this->character, $validated);

        $this->assertSame([], $result['blockers']);
        $this->assertSame(0, $result['progress']['cycle_position']);
        $this->assertSame(0, $result['progress']['crafting_xp_gained']);
        $this->assertSame(0, $result['progress']['enchanting_xp_gained']);
        $this->assertSame([], $result['progress']['kept_best']);
    }

    public function test_resolve_start_for_set_builds_expected_progress(): void
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($armourCrafting, 10, false)
            ->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);
        $character->update(['gold' => 10000]);
        $character = $character->refresh();

        $item = $this->createItem(['name' => 'Setup Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'set',
                'set_positions' => ['body' => $item->id],
                'enchantments' => ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]],
            ],
        ];

        $result = $this->service->resolveStart($character, $validated);

        $this->assertSame('set', $result['progress']['craft_enchant_mode']);
        $this->assertSame(0, $result['progress']['set_index']);
        $this->assertSame('crafting', $result['progress']['set_phase']);
        $this->assertNotEmpty($result['progress']['set_queue']);
        $this->assertSame($prefix->id, $result['progress']['set_queue'][0]['prefix_id']);
    }

    public function test_resolve_start_for_experience_reports_a_blocker_without_meaningful_progression(): void
    {
        $weaponCrafting = GameSkill::where('name', 'Weapon Crafting')->first();
        $this->character->skills()->where('game_skill_id', $weaponCrafting->id)->update(['level' => $weaponCrafting->max_level]);

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => $enchantingGameSkill->max_level]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience'],
        ];

        $result = $this->service->resolveStart($this->character->refresh(), $validated);

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_preview_returns_null_for_experience_mode(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_enchant_mode' => 'experience'],
        ];

        $this->assertNull($this->service->preview($this->character, $validated));
    }

    public function test_preview_returns_a_payload_for_amount_mode(): void
    {
        $item = $this->createItem(['name' => 'Setup Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'prefix_id' => $prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
            ],
        ];

        $preview = $this->service->preview($this->character, $validated);

        $this->assertNotNull($preview);
        $this->assertSame($item->id, $preview['item_id']);
    }
}
