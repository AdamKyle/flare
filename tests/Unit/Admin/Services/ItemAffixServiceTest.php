<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\ItemAffixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Flare\Models\GameMap;
use App\Flare\Models\ItemAffix;
use Tests\Setup\CharacterSetup;
use Tests\Traits\CreateUser;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemAffixServiceTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateItem, CreateItemAffix;

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

        $suffix = $this->item->itemSuffix;

        $service->deleteAffix($this->item->itemSuffix);

        $this->assertNull(ItemAffix::find($suffix->id));

        $foundItem = $this->character->inventory->slots->filter(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $this->assertNull($foundItem);
    }

    public function testItemWithAffixIsDeleted() {
        $service = resolve(ItemAffixService::class);

        $this->character->inventory->slots()->truncate();

        $suffix = $this->item->itemSuffix;

        $service->deleteAffix($this->item->itemSuffix);

        $this->assertNull(ItemAffix::find($suffix->id));
    }

    protected function baseSetUp() {
        $user = $this->createUser();

        $this->item = $this->createItem();

        $this->item->item_suffix_id = $this->createItemAffix()->id;

        $this->item->save();

        $this->item = $this->item->refresh();

        $this->createItem();

        $this->character = (new CharacterSetup)->setupCharacter($user)
                                               ->giveItem($this->item)
                                               ->getCharacter();
    }
}
