<?php

namespace Tests\Feature\Game\Character\CharacterSheet\Controllers\Api;

use App\Game\Core\Items\Values\ItemType;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateFaction;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class CharacterSheetControllerTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateCharacterBoon, CreateFaction, CreateGameMap, CreateItem, CreateRole, CreateUser, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_character_sheet_returns_heal_for_when_healing_spell_is_equipped()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => ItemType::SPELL_HEALING->value,
            'base_healing' => 100,
        ]);

        $character = $this->character
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
        $character = $this->character->getCharacter();

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
        $character = $this->character->getCharacter();

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

    public function test_stat_details_returns_stat_details_for_the_character(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/stat-details');

        $response->assertOk();
        $this->assertArrayHasKey('stat_details', $response->json());
    }

    public function test_stat_break_down_returns_break_down_details_for_a_stat_type(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/stat-break-down?stat_type=str');

        $response->assertOk();
    }

    public function test_specific_stat_break_down_returns_regular_and_voided_breakdowns(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/specific-attribute-break-down?type=health');

        $response->assertOk();
        $this->assertArrayHasKey('regular', $response->json());
        $this->assertArrayHasKey('voided', $response->json());
    }

    public function test_basic_location_information_returns_position_and_gold(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-location-data/'.$character->id);

        $response->assertOk();
        $this->assertSame($character->map->character_position_x, $response->json('x_position'));
        $this->assertSame($character->map->character_position_y, $response->json('y_position'));
        $this->assertSame($character->gold, $response->json('gold'));
    }

    public function test_name_change_updates_the_characters_name(): void
    {
        $character = $this->character->getCharacter();
        $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($character->user)
            ->postJson('/api/character-sheet/'.$character->id.'/name-change', [
                'name' => 'brandnewname',
            ]);

        $response->assertOk();
        $this->assertSame('brandnewname', $character->refresh()->name);
        $this->assertFalse($character->fresh()->force_name_change);
    }

    public function test_name_change_fails_validation_for_an_invalid_name(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character-sheet/'.$character->id.'/name-change', [
                'name' => 'a',
            ]);

        $response->assertStatus(422);
    }

    public function test_global_time_out_sets_timeout_and_dispatches_end_job(): void
    {
        Queue::fake();
        Event::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character-timeout');

        $response->assertOk();
        $this->assertNotNull($character->user->fresh()->timeout_until);
    }

    public function test_active_boons_returns_boon_rows(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'last_for_minutes' => 30,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/active-boons');

        $response->assertOk();
        $this->assertCount(1, $response->json('active_boons'));
    }

    public function test_active_boons_reports_zero_amount_left_when_character_has_no_alchemy_bag(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'last_for_minutes' => 30,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(30),
        ]);

        $character->alchemyBag()->delete();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/active-boons');

        $response->assertOk();
        $this->assertSame(0, $response->json('active_boons.0.amount_left'));
    }

    public function test_automations_returns_current_automations(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/automations');

        $response->assertOk();
        $this->assertArrayHasKey('automations', $response->json());
    }

    public function test_factions_returns_factions_excluding_inactive_seasonal_event_maps(): void
    {
        $winterMap = $this->createGameMap(['only_during_event_type' => EventType::WINTER_EVENT]);
        $delusionalMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $regularMap = $this->createGameMap();

        $character = $this->character->getCharacter();

        $this->createFaction(['character_id' => $character->id, 'game_map_id' => $winterMap->id]);
        $this->createFaction(['character_id' => $character->id, 'game_map_id' => $delusionalMap->id]);
        $this->createFaction(['character_id' => $character->id, 'game_map_id' => $regularMap->id]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/factions');

        $response->assertOk();
        $factions = $response->json('factions');
        $this->assertCount(1, $factions);
        $this->assertSame($regularMap->id, $factions[0]['game_map_id']);
        $this->assertSame($regularMap->name, $factions[0]['map_name']);
    }

    public function test_skills_returns_character_skills_and_passives(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/skills');

        $response->assertOk();
        $this->assertArrayHasKey('skills', $response->json());
        $this->assertArrayHasKey('passives', $response->json());
    }

    public function test_base_inventory_info_returns_inventory_summary(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character-sheet/'.$character->id.'/base-inventory-info');

        $response->assertOk();
        $this->assertArrayHasKey('gold', $response->json('inventory_info'));
        $this->assertArrayHasKey('to_hit_stat', $response->json('inventory_info'));
    }

    public function test_cancel_boon_returns_422_when_boon_does_not_belong_to_the_character(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $boon = $this->createCharacterBoon([
            'character_id' => $otherCharacter->id,
            'item_id' => $item->id,
            'last_for_minutes' => 30,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character-sheet/'.$character->id.'/remove-boon/'.$boon->id);

        $response->assertStatus(422);
        $this->assertSame('You cannot do that.', $response->json('message'));
    }

    public function test_cancel_boon_removes_the_boon(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'last_for_minutes' => 30,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character-sheet/'.$character->id.'/remove-boon/'.$boon->id);

        $response->assertOk();
        $this->assertSame('Boon has been deleted', $response->json('message'));
        $this->assertNull($boon->fresh());
    }

    public function test_fill_up_boon_returns_422_when_boon_does_not_belong_to_the_character(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $boon = $this->createCharacterBoon([
            'character_id' => $otherCharacter->id,
            'item_id' => $item->id,
            'last_for_minutes' => 30,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $response->assertStatus(422);
        $this->assertSame('You cannot do that.', $response->json('message'));
    }

    public function test_fill_up_boon_returns_error_result_when_boon_is_no_longer_active(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'last_for_minutes' => 30,
            'amount_used' => 1,
            'started' => now()->subHour(),
            'complete' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $response->assertStatus(422);
        $this->assertSame('This boon is no longer active.', $response->json('message'));
    }

    public function test_fill_up_boon_extends_the_boon_when_alchemy_stock_is_available(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true, 'can_stack' => true, 'lasts_for' => 30]);
        $character = $this->character->getCharacter();
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 5,
        ]);
        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'last_for_minutes' => 30,
            'amount_used' => 1,
            'started' => now()->subMinutes(10),
            'complete' => now()->addMinutes(20),
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $response->assertOk();
        $this->assertArrayHasKey('boons', $response->json());
        $this->assertSame(4, $character->alchemyBag->slots()->where('item_id', $item->id)->first()->amount);
        $this->assertSame(30, $boon->fresh()->last_for_minutes);
    }
}
