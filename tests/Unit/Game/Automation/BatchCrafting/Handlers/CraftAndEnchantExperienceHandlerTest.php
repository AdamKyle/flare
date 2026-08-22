<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantExperienceHandler;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantExperienceHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?CraftAndEnchantExperienceHandler $handler;

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

        $this->createItem(['name' => 'Experience Dagger', 'type' => 'dagger', 'default_position' => 'dagger', 'crafting_type' => 'weapon', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 0]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $this->handler = resolve(CraftAndEnchantExperienceHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->handler = null;
    }

    public function test_handle_crafts_and_enchants_and_tracks_both_xp_totals(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $progress = $batchCrafting->refresh()->progress;
        $this->assertNotNull($progress['current_item_name']);
        $this->assertNotNull($progress['current_prefix_name']);
    }

    public function test_handle_keep_places_item_in_crafted_items_set(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->count());
    }

    public function test_handle_ends_maxed_or_nothing_left_when_no_meaningful_work_remains(): void
    {
        $weaponCrafting = GameSkill::where('name', 'Weapon Crafting')->first();
        $this->character->skills()->where('game_skill_id', $weaponCrafting->id)->update(['level' => 400]);

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => $enchantingGameSkill->max_level]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
    }

    public function test_handle_keep_best_sell_rest_retains_the_first_crafted_item(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->count());
        $this->assertNotEmpty($batchCrafting->refresh()->progress['kept_best']);
    }

    public function test_handle_list_disposition_creates_a_market_listing(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => 250,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
    }

    public function test_handle_ends_no_enchanting_affix_when_none_exist(): void
    {
        ItemAffix::query()->delete();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_ENCHANTING_AFFIX, $result->endReason());
    }

    public function test_handle_ends_int_too_low_without_choosing_a_weaker_affix(): void
    {
        ItemAffix::where('type', 'prefix')->update(['int_required' => 100000]);
        $this->character->update(['int' => 1]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW, $result->endReason());
    }

    public function test_handle_returns_the_failed_craft_and_enchant_result_when_the_enchant_roll_fails(): void
    {
        $weaponCraftingSkill = $this->character->skills()->whereHas('baseSkill', fn ($query) => $query->where('name', 'Weapon Crafting'))->first();
        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $enchantingSkill = $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->first();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) use ($weaponCraftingSkill, $enchantingSkill): void {
                $mock->shouldReceive('getDCCheck')->withArgs(fn ($skill) => $skill->id === $weaponCraftingSkill->id)->andReturn(1);
                $mock->shouldReceive('characterRoll')->withArgs(fn ($skill) => $skill->id === $weaponCraftingSkill->id)->andReturn(100);
                $mock->shouldReceive('getDCCheck')->withArgs(fn ($skill) => $skill->id === $enchantingSkill->id)->andReturn(1000);
                $mock->shouldReceive('characterRoll')->withArgs(fn ($skill) => $skill->id === $enchantingSkill->id)->andReturn(1);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = resolve(CraftAndEnchantExperienceHandler::class)->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertNull($result->endReason());
    }

    public function test_handle_keep_ends_batch_crafting_set_full_when_the_crafted_items_set_is_full(): void
    {
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'enchanting_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
                'current_crafting_type' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL, $result->endReason());
    }
}
