<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\AlchemyExperienceHandler;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class AlchemyExperienceHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?Item $alchemyItem;

    private ?AlchemyExperienceHandler $handler;

    private array $progress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alchemyItem = $this->createItem([
            'gold_dust_cost' => 100,
            'shards_cost' => 10,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST, 'shards' => CurrencyLimit::MAX_SHARDS]);
        $this->character = $this->character->refresh();

        $this->progress = ['alchemy_mode' => 'experience', 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null];

        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $this->handler = resolve(AlchemyExperienceHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->alchemyItem = null;
        $this->handler = null;
    }

    public function test_handle_ends_with_skill_maxed_when_alchemy_skill_is_maxed(): void
    {
        $skill = $this->character->skills()->where('game_skill_id', GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first()->id)->first();
        $skill->baseSkill()->update(['max_level' => 1]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::SKILL_MAXED, $result->endReason());
    }

    public function test_handle_ends_with_maxed_or_nothing_left_when_no_meaningful_item_exists(): void
    {
        Item::where('crafting_type', 'alchemy')->update(['skill_level_trivial' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
    }

    public function test_handle_ends_with_no_gold_dust_when_insufficient(): void
    {
        $this->character->update(['gold_dust' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::NO_GOLD_DUST, $result->endReason());
    }

    public function test_handle_ends_with_no_shards_when_insufficient(): void
    {
        $this->character->update(['shards' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::NO_SHARDS, $result->endReason());
    }

    public function test_handle_returns_failed_result_when_the_transmute_roll_fails(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $this->handler = resolve(AlchemyExperienceHandler::class);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
    }

    public function test_handle_transmutes_and_accumulates_xp_and_resource_spending(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->fresh()->progress;

        $this->assertNull($result->endReason());
        $this->assertSame($this->alchemyItem->id, $progress['current_item_id']);
        $this->assertSame(100, $result->goldDustSpent());
        $this->assertSame(10, $result->shardsSpent());
    }

    public function test_handle_keep_best_destroy_rest_stacks_the_same_repeated_item(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => $this->progress,
        ]);

        $this->handler->handle($batchCrafting, $this->character);
        $this->handler->handle($batchCrafting->fresh(), $this->character->refresh());

        $slot = $this->character->refresh()->alchemyBag->slots()->where('item_id', $this->alchemyItem->id)->first();

        $this->assertNotNull($slot);
        $this->assertSame(2, $slot->amount);
    }
}
