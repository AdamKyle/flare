<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemController extends TestCase
{
    use RefreshDatabase,
        CreateItem;

    public function setUp(): void {
        parent::setUp();

        $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6',
        ]);
    }

    public function testCanSeeItemDetails() {
        $this->visitRoute('items.item', ['item' => 1])->see('Rusty Dagger');
    }

    public function testCannotSeeItemDetailsFourOhFour() {
        $response = $this->get(route('items.item', [
            'item' => 100
        ]))->response;

        $this->assertEquals($response->status(), 404);
    }
}
