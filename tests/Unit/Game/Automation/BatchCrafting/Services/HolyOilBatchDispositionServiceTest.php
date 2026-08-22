<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Services\HolyOilBatchDispositionService;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class HolyOilBatchDispositionServiceTest extends TestCase
{
    use CreateGameSkill, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?HolyOilBatchDispositionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->service = resolve(HolyOilBatchDispositionService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_apply_keeps_the_inventory_target_by_default(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->service->apply($character, BatchCraftingDisposition::KEEP, $slot, null);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $character->inventory->slots()->where('id', $slot->id)->count());
    }

    public function test_apply_sells_an_inventory_slot_target_through_the_real_sale_event(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($item)->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->service->apply($character, BatchCraftingDisposition::SELL, $slot, null);

        $this->assertSame(BatchCraftingActionStatus::SOLD, $result->actionStatus());
        $this->assertEquals(0, $character->inventory->slots()->where('id', $slot->id)->count());
        $this->assertGreaterThan(0, $character->refresh()->gold);
    }

    public function test_apply_sells_a_set_slot_target_directly_and_credits_gold(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $slot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $this->character->update(['gold' => 0]);
        $this->character = $this->character->refresh();

        $result = $this->service->apply($this->character, BatchCraftingDisposition::SELL, $slot, null);

        $this->assertSame(BatchCraftingActionStatus::SOLD, $result->actionStatus());
        $this->assertGreaterThan(0, $result->goldGained());
        $this->assertEquals(0, $set->slots()->where('id', $slot->id)->count());
        $this->assertSame($result->goldGained(), $this->character->refresh()->gold);
    }

    public function test_apply_destroys_an_inventory_slot_target(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->service->apply($character, BatchCraftingDisposition::DESTROY, $slot, null);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
        $this->assertEquals(0, $character->inventory->slots()->where('id', $slot->id)->count());
    }

    public function test_apply_destroys_a_set_slot_target(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $slot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $result = $this->service->apply($this->character, BatchCraftingDisposition::DESTROY, $slot, null);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
        $this->assertEquals(0, $set->slots()->where('id', $slot->id)->count());
    }

    public function test_apply_lists_an_inventory_slot_target_on_the_market(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->service->apply($character, BatchCraftingDisposition::LIST, $slot, 40);

        $this->assertSame(BatchCraftingActionStatus::LISTED, $result->actionStatus());
        $this->assertEquals(0, $character->inventory->slots()->where('id', $slot->id)->count());
        $this->assertDatabaseHas('market_board', ['item_id' => $item->id, 'listed_price' => 40]);
    }

    public function test_apply_disenchants_an_inventory_slot_target_through_the_disenchant_domain(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
                $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
            })
        );

        $disenchantingSkill = $this->createGameSkill([
            'name' => 'Disenchanting',
            'type' => SkillTypeValue::DISENCHANTING->value,
        ]);
        $item = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignSkill($disenchantingSkill)
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = resolve(HolyOilBatchDispositionService::class)->apply($character, BatchCraftingDisposition::DISENCHANT, $slot, null);

        $this->assertSame(BatchCraftingActionStatus::DISENCHANTED, $result->actionStatus());
        $this->assertEquals(0, $character->inventory->slots()->where('id', $slot->id)->count());
    }
}
