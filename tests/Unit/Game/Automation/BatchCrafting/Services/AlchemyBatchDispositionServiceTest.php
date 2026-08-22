<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Services\AlchemyBatchDispositionService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateItem;

class AlchemyBatchDispositionServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateCharacterBoon, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?AlchemyBatchDispositionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->service = resolve(AlchemyBatchDispositionService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_apply_keeps_the_produced_item_in_the_alchemy_bag(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy']);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->service->apply($this->character, BatchCraftingDisposition::KEEP, $item, $slot->id, null);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $this->character->alchemyBag->slots()->where('id', $slot->id)->count());
    }

    public function test_apply_destroys_the_produced_item_through_the_inventory_domain_path(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy']);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->service->apply($this->character, BatchCraftingDisposition::DESTROY, $item, $slot->id, null);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
        $this->assertSame(0, $this->character->alchemyBag->slots()->where('id', $slot->id)->count());
    }

    public function test_apply_lists_the_produced_item_through_the_market_domain_and_removes_it_from_the_bag(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy']);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->service->apply($this->character, BatchCraftingDisposition::LIST, $item, $slot->id, 25);

        $this->assertSame(BatchCraftingActionStatus::LISTED, $result->actionStatus());
        $this->assertSame(0, $this->character->alchemyBag->slots()->where('id', $slot->id)->count());
        $this->assertDatabaseHas('market_board', ['item_id' => $item->id, 'listed_price' => 25]);
    }

    public function test_apply_uses_the_produced_item_immediately_when_the_domain_allows_it(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'usable' => true,
            'lasts_for' => 30,
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->service->apply($this->character, BatchCraftingDisposition::USE_NOW, $item, $slot->id, null);

        $this->assertSame(BatchCraftingActionStatus::USED, $result->actionStatus());
    }

    public function test_apply_keeps_when_the_use_now_slot_no_longer_exists(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy']);

        $result = $this->service->apply($this->character, BatchCraftingDisposition::USE_NOW, $item, 999999, null);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
    }

    public function test_apply_retains_the_produced_item_when_immediate_use_is_blocked_by_domain_rules(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'usable' => true,
            'lasts_for' => 30,
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);
        $this->createCharacterBoon([
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 10,
            'last_for_minutes' => 120,
        ]);

        $result = $this->service->apply($this->character->refresh(), BatchCraftingDisposition::USE_NOW, $item, $slot->id, null);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $this->character->alchemyBag->slots()->where('id', $slot->id)->count());
    }
}
