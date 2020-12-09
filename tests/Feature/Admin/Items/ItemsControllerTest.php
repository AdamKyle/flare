<?php

namespace Tests\Feature\Admin\Items;

use Event;
use Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateItem;

class ItemsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateItem;

    private $user;

    protected $item;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin([], $role);

        $this->item = $this->createItem();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user  = null;
        $this->item = null;
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('items.list'))->see('Items');
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('items.create'))->see('Create item');
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('items.item', [
            'item' => $this->item->id,
        ]))->see($this->item->name);
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('items.edit', [
            'item' => $this->item->id,
        ]))->see('Edit item: ' . $this->item->name);
    }

    public function testCanDelete() {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->user)->post(route('items.delete', [
            'item' => $this->item->id,
        ]));


        $this->assertNull(ItemAffix::find($this->item->id));
    }

    public function testCanDeleteWhenAttachedToCharacter() {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->inventoryManagement()
                                           ->giveItem($this->item)
                                           ->equipLeftHand()
                                           ->getCharacterFactory();

        $this->actingAs($this->user)->post(route('items.delete', [
            'item' => $this->item->id,
        ]))->response;

        $this->assertNull(Item::find($this->item->id));

        $character = $character->getCharacter();

        $item = $character->inventory->slots->filter(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $this->assertNull($item);
    }
}
