<?php

namespace Tests\Feature\Admin\Items;

use App\Admin\Exports\Items\ItemsExport;
use App\Admin\Exports\Items\NpcsExport;
use Event;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
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

        $this->user = $this->createAdmin($role, []);

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
                                           ->equipLeftHand($this->item->name)
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

    public function testCanDeleteAllItems() {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->inventoryManagement()
                                           ->giveItem($this->item)
                                           ->equipLeftHand($this->item->name)
                                           ->getCharacterFactory();

        $this->actingAs($this->user)->post(route('items.delete.all', [
            'items' => [$this->item->id],
        ]))->response;

        $this->assertNull(Item::find($this->item->id));

        $character = $character->getCharacter();

        $item = $character->inventory->slots->filter(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $this->assertNull($item);
    }

    public function testCanDeleteAllItemsNotAssociatedWithCharacters() {
        Queue::fake();
        Event::fake();

        $this->actingAs($this->user)->post(route('items.delete.all', [
            'items' => [$this->item->id],
        ]))->response;

        $this->assertNull(Item::find($this->item->id));
    }

    public function testCannotDeleteItemsWhenTheyDontExist() {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->user)->post(route('items.delete.all', [
            'items' => [769],
        ]))->response;

        $response->assertSessionHas('error', 'Invalid input.');
    }

    public function testCanSeeExportPageForItems() {
        $this->actingAs($this->user)->visit(route('items.export'))->see('Export');
    }

    public function testCanExportItems() {
        Excel::fake();

        $this->actingAs($this->user)->post(route('items.export-data'));

        Excel::assertDownloaded('items.xlsx', function(ItemsExport $export) {
            return true;
        });
    }

    public function testCanSeeItemsImportPage() {
        $this->actingAs($this->user)->visit(route('items.import'))->see('Import Item Data');
    }

    public function testCanImportItems() {
        $this->actingAs($this->user)->post(route('items.import-data', [
            'items_import' => new UploadedFile(resource_path('data-imports/items.xlsx'), 'items.xlsx')
        ]));

        $this->assertTrue(Item::all()->isNotEmpty());
    }
}
