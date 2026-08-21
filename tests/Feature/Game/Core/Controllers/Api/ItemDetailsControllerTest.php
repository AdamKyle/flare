<?php

namespace Tests\Feature\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemDetailsControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_show_returns_the_transformed_catalog_item_for_an_authenticated_user(): void
    {
        $item = $this->createItem(['name' => 'Catalog Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'cost' => 25]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/item-details/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame($item->id, $jsonData['id']);
        $this->assertSame('Catalog Dagger', $jsonData['name']);
        $this->assertSame(25, $jsonData['cost']);
    }

    public function test_show_does_not_require_a_character_scoped_inventory_slot(): void
    {
        $item = $this->createItem(['name' => 'Unowned Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'cost' => 5]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/item-details/'.$item->id);

        $response->assertOk();
    }
}
