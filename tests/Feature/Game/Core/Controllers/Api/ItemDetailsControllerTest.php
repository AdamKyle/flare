<?php

namespace Tests\Feature\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGameGemAbility;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemSocket;

class ItemDetailsControllerTest extends TestCase
{
    use CreateCharacterGemModifier, CreateGameGemAbility, CreateGem, CreateItem, CreateItemSocket, RefreshDatabase;

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

    public function test_show_returns_socket_capacity_and_canonical_gem_details_in_socket_order(): void
    {
        $item = $this->createItem(['type' => 'dagger', 'socket_count' => 3]);
        $firstGem = $this->createGem(['name' => 'Pharozoisite', 'tier' => 2]);
        $secondGem = $this->createGem(['name' => 'Aetherite', 'tier' => 4]);
        $firstSocket = $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $firstGem->id]);
        $secondSocket = $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $secondGem->id]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/item-details/'.$item->id);

        $response->assertOk()
            ->assertJsonPath('socket_amount', 3)
            ->assertJsonPath('sockets.0.id', $firstSocket->id)
            ->assertJsonPath('sockets.0.position', 1)
            ->assertJsonPath('sockets.0.gem.id', $firstGem->id)
            ->assertJsonPath('sockets.0.gem.name', 'Pharozoisite')
            ->assertJsonPath('sockets.0.gem.tier', 2)
            ->assertJsonPath('sockets.0.gem.modifiers', [])
            ->assertJsonPath('sockets.1.id', $secondSocket->id)
            ->assertJsonPath('sockets.1.position', 2)
            ->assertJsonPath('sockets.1.gem.name', 'Aetherite');
    }

    public function test_show_returns_duplicate_active_ability_metadata_for_an_unequipped_item(): void
    {
        $ability = $this->createGameGemAbility(['name' => 'Flames of the Heretic']);
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();

        foreach ([$firstGem, $secondGem] as $gem) {
            $this->createCharacterGemModifier([
                'gem_id' => $gem->id,
                'roll_position' => 1,
                'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
                'amount' => null,
                'game_gem_ability_id' => $ability->id,
            ]);
        }

        $item = $this->createItem(['type' => 'dagger', 'socket_count' => 2]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $firstGem->id]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $secondGem->id]);

        $response = $this->actingAs($this->character->user)
            ->getJson('/api/item-details/'.$item->id);

        $response->assertOk()
            ->assertJsonPath('duplicate_gem_abilities.0.ability.id', $ability->id)
            ->assertJsonPath('duplicate_gem_abilities.0.ability.name', 'Flames of the Heretic')
            ->assertJsonPath('duplicate_gem_abilities.0.count', 2)
            ->assertJsonPath('duplicate_gem_abilities.0.items.0.item_id', $item->id);
    }
}
