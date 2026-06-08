<?php

namespace Tests\Feature\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ShopBuyAndReplaceControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?Character $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    public function tearDown(): void
    {
        Mockery::close();

        $this->character = null;

        parent::tearDown();
    }

    public function testBuyAndReplaceReturnsGenericErrorWhenReplaceItemFails(): void
    {
        $item = $this->createItem(['type' => 'shield', 'cost' => 100]);
        $this->character->update(['gold' => 50000]);

        $equipItemService = Mockery::mock(EquipItemService::class);
        $equipItemService->shouldReceive('setRequest')->andReturnSelf();
        $equipItemService->shouldReceive('setCharacter')->andReturnSelf();
        $equipItemService->shouldReceive('replaceItem')->andThrow(new EquipItemException('Cannot equip another unique item.'));

        $this->instance(EquipItemService::class, $equipItemService);

        $response = $this->actingAs($this->character->user)
            ->json('POST', '/api/shop/buy-and-replace/' . $this->character->id, [
                'item_id_to_buy' => $item->id,
                'position' => 'left-hand',
                'slot_id' => 1,
                'equip_type' => 'shield',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Could not complete purchase.']);
        $response->assertJsonMissing(['message' => 'Cannot equip another unique item.']);
    }
}
