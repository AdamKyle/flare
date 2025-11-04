<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\ItemsService;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\Location;
use App\Flare\Models\SetSlot;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemsServiceTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    private ?ItemsService $service = null;

    private ?CharacterFactory $characterFactory = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new ItemsService();
        $this->characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
        $this->characterFactory = null;
    }

    public function test_form_inputs_structure_and_static_values(): void
    {
        $inputs = $this->service->formInputs();

        $this->assertIsArray($inputs);
        $this->assertArrayHasKey('types', $inputs);
        $this->assertArrayHasKey('defaultPositions', $inputs);
        $this->assertArrayHasKey('craftingTypes', $inputs);
        $this->assertArrayHasKey('skillTypes', $inputs);
        $this->assertArrayHasKey('effects', $inputs);
        $this->assertArrayHasKey('specialtyTypes', $inputs);
        $this->assertArrayHasKey('itemSkills', $inputs);
        $this->assertArrayHasKey('locations', $inputs);
        $this->assertArrayHasKey('skills', $inputs);
        $this->assertArrayHasKey('classes', $inputs);

        $this->assertContains('weapon', $inputs['types']);
        $this->assertContains('wand', $inputs['types']);
        $this->assertContains('body', $inputs['defaultPositions']);
        $this->assertContains('alchemy', $inputs['craftingTypes']);
        $this->assertEquals(SkillTypeValue::getValues(), $inputs['skillTypes']);
        $this->assertContains(ItemEffectsValue::WALK_ON_WATER, $inputs['effects']);
        $this->assertContains(ItemEffectsValue::HELL, $inputs['effects']);
        $this->assertContains(ItemSpecialtyType::HELL_FORGED, $inputs['specialtyTypes']);
        $this->assertContains(ItemSpecialtyType::DELUSIONAL_SILVER, $inputs['specialtyTypes']);
    }

    public function test_form_inputs_includes_database_driven_arrays(): void
    {
        ItemSkill::create([
            'name' => 'Blade Mastery',
            'description' => 'x',
            'max_level' => 10,
            'total_kills_needed' => 0,
            'parent_id' => null,
            'parent_level_needed' => 0,
        ]);

        Location::factory()->create();
        GameSkill::factory()->create();
        GameClass::factory()->create();

        $inputs = $this->service->formInputs();

        $this->assertTrue($inputs['itemSkills']->count() >= 1);
        $this->assertNotEmpty($inputs['locations']);
        $this->assertIsArray($inputs['skills']);
        $this->assertIsArray($inputs['classes']);
    }

    public function test_clean_request_data_sets_effect_to_null_when_type_not_quest(): void
    {
        $params = $this->baseParams();
        $params['type'] = 'alchemy';
        $params['effect'] = ItemEffectsValue::HELL;

        $result = $this->service->cleanRequestData($params);

        $this->assertNull($result['effect']);
    }

    public function test_clean_request_data_keeps_effect_when_type_is_quest(): void
    {
        $params = $this->baseParams();
        $params['type'] = 'quest';
        $params['effect'] = ItemEffectsValue::HELL;

        $result = $this->service->cleanRequestData($params);

        $this->assertEquals(ItemEffectsValue::HELL, $result['effect']);
    }

    public function test_clean_request_data_disables_can_use_on_other_items_and_clears_holy_level(): void
    {
        $params = $this->baseParams();
        $params['can_use_on_other_items'] = false;
        $params['holy_level'] = 10;

        $result = $this->service->cleanRequestData($params);

        $this->assertFalse($result['can_use_on_other_items']);
        $this->assertNull($result['holy_level']);
    }

    public function test_clean_request_data_preserves_holy_level_when_can_use_on_other_items_true(): void
    {
        $params = $this->baseParams();
        $params['can_use_on_other_items'] = true;
        $params['holy_level'] = 7;

        $result = $this->service->cleanRequestData($params);

        $this->assertTrue($result['can_use_on_other_items']);
        $this->assertSame(7, $result['holy_level']);
    }

    public function test_clean_request_data_disables_usable_and_clears_dependent_fields(): void
    {
        $params = $this->baseParams();
        $params['usable'] = false;
        $params['lasts_for'] = 30;
        $params['damages_kingdoms'] = true;
        $params['stat_increase'] = true;
        $params['affects_skill_type'] = SkillTypeValue::TRAINING;

        $result = $this->service->cleanRequestData($params);

        $this->assertFalse($result['usable']);
        $this->assertNull($result['lasts_for']);
        $this->assertFalse($result['damages_kingdoms']);
        $this->assertFalse($result['stat_increase']);
        $this->assertNull($result['affects_skill_type']);
    }

    public function test_clean_request_data_damages_kingdoms_false_clears_kingdom_damage(): void
    {
        $params = $this->baseParams();
        $params['damages_kingdoms'] = false;
        $params['kingdom_damage'] = 12.5;

        $result = $this->service->cleanRequestData($params);

        $this->assertFalse($result['damages_kingdoms']);
        $this->assertNull($result['kingdom_damage']);
    }

    public function test_clean_request_data_damages_kingdoms_true_overrides_and_clears_other_fields(): void
    {
        $params = $this->baseParams();
        $params['usable'] = true;
        $params['damages_kingdoms'] = true;
        $params['lasts_for'] = 30;
        $params['stat_increase'] = true;
        $params['affects_skill_type'] = SkillTypeValue::TRAINING;

        $result = $this->service->cleanRequestData($params);

        $this->assertTrue($result['damages_kingdoms']);
        $this->assertNull($result['lasts_for']);
        $this->assertFalse($result['stat_increase']);
        $this->assertNull($result['affects_skill_type']);
    }

    public function test_clean_request_data_stat_increase_false_sets_increase_stat_by_zero(): void
    {
        $params = $this->baseParams();
        $params['stat_increase'] = false;
        $params['increase_stat_by'] = 25.0;

        $result = $this->service->cleanRequestData($params);

        $this->assertFalse($result['stat_increase']);
        $this->assertSame(0, $result['increase_stat_by']);
    }

    public function test_clean_request_data_affects_skill_type_null_clears_skill_bonus_increases(): void
    {
        $params = $this->baseParams();
        $params['affects_skill_type'] = null;
        $params['increase_skill_bonus_by'] = 5.5;
        $params['increase_skill_training_bonus_by'] = 3.5;

        $result = $this->service->cleanRequestData($params);

        $this->assertNull($result['increase_skill_bonus_by']);
        $this->assertNull($result['increase_skill_training_bonus_by']);
    }

    public function test_clean_request_data_can_resurrect_false_clears_resurrection_chance(): void
    {
        $params = $this->baseParams();
        $params['can_resurrect'] = false;
        $params['resurrection_chance'] = 0.75;

        $result = $this->service->cleanRequestData($params);

        $this->assertFalse($result['can_resurrect']);
        $this->assertNull($result['resurrection_chance']);
    }

    public function test_clean_request_data_can_craft_false_clears_crafting_fields(): void
    {
        $params = $this->baseParams();
        $params['can_craft'] = false;
        $params['crafting_type'] = 'weapon';
        $params['craft_only'] = true;
        $params['skill_level_required'] = 100;
        $params['skill_level_trivial'] = 200;

        $result = $this->service->cleanRequestData($params);

        $this->assertFalse($result['can_craft']);
        $this->assertNull($result['crafting_type']);
        $this->assertFalse($result['craft_only']);
        $this->assertNull($result['skill_level_required']);
        $this->assertNull($result['skill_level_trivial']);
    }

    public function test_delete_item_removes_inventory_and_set_slots_and_deletes_item_and_children(): void
    {
        $root = $this->createItem([
            'name' => 'Root Item',
            'type' => 'weapon',
        ]);

        $child = $this->createItem([
            'name' => 'Child Item',
            'type' => 'weapon',
            'parent_id' => $root->id,
        ]);

        $character = $this->characterFactory
            ->inventoryManagement()
            ->giveItem($root)
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($root, 0)
            ->putItemInSet($child, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($child)
            ->getCharacter();

        $this->assertTrue(InventorySlot::where('item_id', $root->id)->count() > 0);
        $this->assertTrue(SetSlot::where('item_id', $root->id)->count() > 0);
        $this->assertTrue(InventorySlot::where('item_id', $child->id)->count() > 0);
        $this->assertTrue(SetSlot::where('item_id', $child->id)->count() > 0);

        $response = $this->service->deleteItem($root);

        $this->assertNull(Item::find($root->id));
        $this->assertNull(Item::find($child->id));
        $this->assertSame(0, InventorySlot::where('item_id', $root->id)->count());
        $this->assertSame(0, SetSlot::where('item_id', $root->id)->count());
        $this->assertSame(0, InventorySlot::where('item_id', $child->id)->count());
        $this->assertSame(0, SetSlot::where('item_id', $child->id)->count());
        $this->assertIsArray($response);
        $this->assertNotEmpty($response);
    }

    public function test_delete_item_when_item_has_no_associations(): void
    {
        $item = $this->createItem([
            'name' => 'Lonely',
            'type' => 'weapon',
        ]);

        $response = $this->service->deleteItem($item);

        $this->assertNull(Item::find($item->id));
        $this->assertSame(0, InventorySlot::where('item_id', $item->id)->count());
        $this->assertSame(0, SetSlot::where('item_id', $item->id)->count());
        $this->assertIsArray($response);
        $this->assertNotEmpty($response);
    }

    private function baseParams(): array
    {
        return [
            'type' => 'weapon',
            'effect' => null,
            'can_use_on_other_items' => false,
            'holy_level' => null,
            'usable' => false,
            'lasts_for' => null,
            'damages_kingdoms' => false,
            'stat_increase' => false,
            'affects_skill_type' => null,
            'kingdom_damage' => null,
            'increase_stat_by' => 0.0,
            'increase_skill_bonus_by' => null,
            'increase_skill_training_bonus_by' => null,
            'can_resurrect' => false,
            'resurrection_chance' => null,
            'can_craft' => false,
            'crafting_type' => null,
            'craft_only' => false,
            'skill_level_required' => null,
            'skill_level_trivial' => null,
        ];
    }
}
