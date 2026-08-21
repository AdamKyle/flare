<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\CraftExperienceHandler;
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

class CraftExperienceHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CharacterFactory $characterFactory;

    private ?Character $character;

    private ?CraftExperienceHandler $handler;

    private array $progress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->character = $this->characterFactory->getCharacter();

        $this->character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->handler = resolve(CraftExperienceHandler::class);

        $this->progress = [
            'craft_mode' => 'experience',
            'cycle_position' => 0,
            'crafting_xp_gained' => 0,
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
        $this->character = null;
        $this->handler = null;
    }

    public function test_handle_ends_skill_maxed_when_all_four_crafting_skills_are_maxed(): void
    {
        foreach (['Weapon Crafting', 'Armour Crafting', 'Ring Crafting', 'Spell Crafting'] as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

            $this->character = $this->characterFactory->assignSkill($skill, 5, false)->getCharacter();
        }

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::SKILL_MAXED, $result->endReason());
    }

    public function test_handle_crafts_a_meaningful_item_for_a_non_maxed_weapon_skill(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = $this->characterFactory->assignSkill($skill, 10, false)->getCharacter();

        $this->createItem(['name' => 'Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => $this->progress,
        ]);

        $this->handler->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame('Experience Dagger', $progress['current_item_name']);
        $this->assertSame('weapon', $progress['current_crafting_type']);
    }

    public function test_handle_advances_cycle_position_after_the_action(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = $this->characterFactory->assignSkill($skill, 10, false)->getCharacter();

        $this->createItem(['name' => 'Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => $this->progress,
        ]);

        $this->handler->handle($batchCrafting, $this->character);

        $this->assertNotSame(0, $batchCrafting->refresh()->progress['cycle_position']);
    }

    public function test_handle_keep_places_the_crafted_item_in_the_crafted_items_set(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = $this->characterFactory->assignSkill($skill, 10, false)->getCharacter();

        $item = $this->createItem(['name' => 'Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $result = resolve(CraftExperienceHandler::class)->handle($batchCrafting, $this->character);

        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $item->id)->count());
    }

    public function test_handle_keep_best_sell_rest_retains_the_first_crafted_item(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = $this->characterFactory->assignSkill($skill, 10, false)->getCharacter();

        $item = $this->createItem(['name' => 'Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => $this->progress,
        ]);

        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $result = resolve(CraftExperienceHandler::class)->handle($batchCrafting, $this->character);

        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $slot = $batchCraftingSet->slots()->where('item_id', $item->id)->first();
        $progress = $batchCrafting->refresh()->progress;

        $this->assertTrue($result->didCraft());
        $this->assertNotNull($slot);
        $this->assertEquals(
            ['dagger' => ['item_id' => $item->id, 'set_slot_id' => $slot->id, 'quality' => $item->skill_level_required]],
            $progress['kept_best']
        );
    }

    public function test_handle_keep_ends_batch_crafting_set_full_when_the_crafted_items_set_has_no_room(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = $this->characterFactory->assignSkill($skill, 10, false)->getCharacter();

        $this->createItem(['name' => 'Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertNotNull($result->endReason());
    }

    public function test_handle_keep_best_translates_a_failed_roll_into_a_failed_result(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = $this->characterFactory->assignSkill($skill, 10, false)->getCharacter();

        $this->createItem(['name' => 'Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => $this->progress,
        ]);

        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $result = resolve(CraftExperienceHandler::class)->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
    }

    public function test_handle_ends_maxed_or_nothing_left_when_no_meaningful_target_remains(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = $this->characterFactory->assignSkill($skill, 10, false)->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
    }
}
