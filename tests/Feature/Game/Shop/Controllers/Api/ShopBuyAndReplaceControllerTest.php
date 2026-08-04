<?php

namespace Tests\Feature\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\InventoryManagement;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ShopBuyAndReplaceControllerTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    protected function tearDown(): void
    {
        $this->character = null;

        parent::tearDown();
    }

    public function test_buy_and_replace_returns_generic_error_when_replacement_is_invalid(): void
    {
        $existingUniquePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);
        $existingUniqueItem = $this->createItem(['type' => 'shield', 'item_prefix_id' => $existingUniquePrefix->id]);
        $existingLeftHandShield = $this->createItem(['type' => 'shield']);

        $newUniquePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);
        $newUniqueShield = $this->createItem(['type' => 'shield', 'item_prefix_id' => $newUniquePrefix->id, 'cost' => 100]);

        $character = (new InventoryManagement($this->character))
            ->giveItem($existingUniqueItem, true, 'right-hand')
            ->giveItem($existingLeftHandShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingLeftHandShield->id);

        $character->update(['gold' => 50000]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/shop/buy-and-replace/'.$character->id, [
                'item_id_to_buy' => $newUniqueShield->id,
                'position' => 'left-hand',
                'slot_id' => $equippedSlot->id,
                'equip_type' => 'shield',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Could not complete purchase.']);
        $response->assertJsonMissing(['message' => 'Cannot equip another unique.']);

        $character = $character->refresh();

        $this->assertSame(50000, $character->gold);
        $this->assertNull($character->inventory->slots->firstWhere('item_id', $newUniqueShield->id));
    }
}
