<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\ItemAffixService;
use App\Flare\Models\ItemAffix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class ItemAffixServiceTest extends TestCase
{
    use CreateItem, CreateItemAffix;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setup();

        $this->baseSetUp();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testItemWithAffixIsDeletedAndCharacterItemIsSwapped() {
        $service = resolve(ItemAffixService::class);

        $suffix = $this->item->itemPrefix;

        $service->deleteAffix($this->item->itemPrefix);

        $this->assertNull(ItemAffix::find($suffix->id));

        $foundItem = $this->character->inventory->slots->filter(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $this->assertNull($foundItem);
    }

    public function testItemWithAffixIsDeleted() {
        $service = resolve(ItemAffixService::class);

        $this->character->inventory->slots()->truncate();

        $prefix = $this->item->itemPrefix;

        $service->deleteAffix($this->item->itemPrefix);

        $this->assertNull(ItemAffix::find($prefix->id));
    }

    protected function baseSetUp() {
        $this->item = $this->createItem();

        $this->item->item_prefix_id = $this->createItemAffix()->id;

        $this->item->save();

        $this->item = $this->item->refresh();

        $this->createItem();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->inventoryManagement()
                                                 ->giveItem($this->item)
                                                 ->getCharacterFactory()
                                                 ->getCharacter(false);
    }
}
