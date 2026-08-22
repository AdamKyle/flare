<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\TrinketryHandler;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Core\Currency\Services\CurrencyLimit;
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

class TrinketryHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?GameSkill $trinketSkill;

    private ?Item $trinket;

    private array $progress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trinketSkill = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->trinket = $this->createItem([
            'type' => 'trinket',
            'can_craft' => true,
            'gold_dust_cost' => 100,
            'copper_coin_cost' => 50,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill($this->trinketSkill, 1, false)->givePlayerLocation()->getCharacter();
        $this->character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST, 'copper_coins' => CurrencyLimit::MAX_COPPER]);
        $this->character = $this->character->refresh();

        $this->progress = ['trinketry_mode' => 'experience', 'trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->trinketSkill = null;
        $this->trinket = null;
    }

    public function test_handle_ends_with_no_trinketry_items_when_no_meaningful_item_exists(): void
    {
        Item::where('type', 'trinket')->update(['skill_level_trivial' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_TRINKETRY_ITEMS, $result->endReason());
    }

    public function test_handle_ends_with_no_gold_dust_when_insufficient(): void
    {
        $this->character->update(['gold_dust' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::NO_GOLD_DUST, $result->endReason());
    }

    public function test_handle_ends_with_no_copper_coins_when_insufficient(): void
    {
        $this->character->update(['copper_coins' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::NO_COPPER_COINS, $result->endReason());
    }

    public function test_handle_keeps_crafted_trinket_in_crafted_items_set(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->fresh()->progress;

        $this->assertTrue($result->didCraft());
        $this->assertSame($this->trinket->id, $progress['current_item_id']);
        $this->assertSame(100, $result->goldDustSpent());
        $this->assertSame(50, $result->copperCoinsSpent());
    }

    public function test_handle_keeps_best_crafted_trinket_when_disposition_keeps_best(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame($this->trinket->skill_level_required, $batchCrafting->fresh()->progress['trinketry_kept_best']['quality']);
    }

    public function test_handle_returns_failed_when_the_craft_roll_fails_for_keep_best(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertNull($result->endReason());
    }

    public function test_handle_returns_failed_when_the_craft_roll_fails_for_a_direct_disposition(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertNull($result->endReason());
    }

    public function test_handle_ends_when_the_crafted_items_set_cannot_accept_the_trinket(): void
    {
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL, $result->endReason());
    }

    public function test_handle_ends_with_skill_maxed_when_trinketry_skill_is_maxed(): void
    {
        $skill = $this->character->skills()->where('game_skill_id', $this->trinketSkill->id)->first();
        $skill->baseSkill()->update(['max_level' => 1]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::SKILL_MAXED, $result->endReason());
    }

    public function test_handle_destroys_crafted_trinket_when_disposition_is_destroy(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => $this->progress,
        ]);

        $result = resolve(TrinketryHandler::class)->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
    }
}
