<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Flare\Models\Item;
use App\Game\Gems\Services\ItemAtonements;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class ItemAtonementsTest extends TestCase {

    use CreateItem, CreateGem, RefreshDatabase;

    private ?Item $item;

    private ?ItemAtonements $itemAtonements;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void {
        parent::setUp();

        $item = $this->createItem([
            'socket_count' => 2
        ]);

        $gem = $this->createGem([
            'name'                       => 'Sample',
            'tier'                       => 4,
            'primary_atonement_type'     => GemTypeValue::FIRE,
            'secondary_atonement_type'   => GemTypeValue::ICE,
            'tertiary_atonement_type'    => GemTypeValue::WATER,
            'primary_atonement_amount'   => 0.10,
            'secondary_atonement_amount' => 0.25,
            'tertiary_atonement_amount'  => 0.45,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $gem->id,
        ]);

        $this->item = $item->refresh();

        $this->itemAtonements = resolve(ItemAtonements::class);

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->item = null;

        $this->itemAtonements = null;

        $this->characterFactory = null;
    }

    public function testInventoryComparisonIsEmpty() {
        $result = $this->itemAtonements->getAtonements($this->item, collect());

        $this->assertNotEmpty($result['item_atonement']);
        $this->assertEmpty($result['inventory_atonements']);
    }

    public function testInventoryComparisonIsNotEmpty() {
        $character = $this->characterFactory->inventoryManagement()->giveItem($this->item)->getCharacter();

        $inventory = $character->inventory->slots;

        $result = $this->itemAtonements->getAtonements($this->item, $inventory);

        $this->assertNotEmpty($result['item_atonement']);
        $this->assertNotEmpty($result['inventory_atonements']);
    }
}
