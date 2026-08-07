<?php

namespace Tests\Feature\Game\Character\CharacterSheet\Controllers\Api;

use App\Game\Core\Items\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterSheetControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_character_sheet_returns_heal_for_when_healing_spell_is_equipped()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => ItemType::SPELL_HEALING->value,
            'base_healing' => 100,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'spell-one')
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character-sheet/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertGreaterThan(0, $jsonData['data']['healing_amount']);
    }

    public function test_character_sheet_returns_complete_contract_with_numeric_values_and_nested_resources()
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character-sheet/'.$character->id);

        $data = json_decode($response->getContent(), true)['data'];

        $this->assertSame($character->id, $data['id']);
        $this->assertSame($character->user_id, $data['user_id']);
        $this->assertSame($character->name, $data['name']);
        $this->assertIsInt($data['level']);
        $this->assertIsInt($data['max_level']);
        $this->assertIsInt($data['gold']);
        $this->assertIsInt($data['gold_dust']);
        $this->assertIsInt($data['shards']);
        $this->assertIsInt($data['copper_coins']);
        $this->assertIsInt($data['gold_bars']);
        $this->assertIsNumeric($data['str_raw']);
        $this->assertIsNumeric($data['str_modded']);
        $this->assertIsNumeric($data['attack']);
        $this->assertIsNumeric($data['ac']);
        $this->assertIsNumeric($data['health']);
        $this->assertIsNumeric($data['resurrection_chance']);
        $this->assertIsArray($data['inventory_count']);
        $this->assertIsArray($data['resistance_info']);
        $this->assertIsArray($data['elemental_atonements']);
        $this->assertIsArray($data['reincarnation_info']);
        $this->assertArrayHasKey('can_craft', $data);
        $this->assertArrayHasKey('is_automation_running', $data);
    }

    public function test_character_sheet_inventory_count_is_a_flat_object_without_a_nested_data_key()
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character-sheet/'.$character->id);

        $inventoryCount = json_decode($response->getContent(), true)['data']['inventory_count'];

        $this->assertArrayNotHasKey('data', $inventoryCount);
        $this->assertIsNumeric($inventoryCount['inventory_count']);
        $this->assertIsNumeric($inventoryCount['inventory_max']);
        $this->assertSame([
            'inventory_max',
            'inventory_count',
            'inventory_bag_count',
            'alchemy_item_count',
            'alchemy_bag_count',
            'alchemy_bag_limit',
            'is_alchemy_bag_full',
            'gem_bag_count',
            'gem_bag_limit',
            'is_gem_bag_full',
            'crafted_items_set_count',
            'crafted_items_set_max',
        ], array_keys($inventoryCount));
    }
}
