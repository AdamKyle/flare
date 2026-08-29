<?php

namespace Tests\Unit\Admin\Items\Imports\Sheets;

use App\Admin\Items\Imports\Sheets\ItemsSheet;
use App\Admin\Items\Services\ItemService;
use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateLocation;

class ItemsSheetTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateGameSkill, CreateItem, CreateItemSkill, CreateLocation, RefreshDatabase;

    public function test_valid_workbook_row_creates_a_new_item(): void
    {
        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'cost']),
            collect(['Imported Sword', 'weapon', 100]),
        ]));

        $item = Item::where('name', 'Imported Sword')->first();

        $this->assertNotNull($item);
        $this->assertSame('weapon', $item->type);
    }

    public function test_valid_workbook_row_updates_existing_item_by_name(): void
    {
        $item = $this->createItem(['name' => 'Existing Item', 'type' => 'weapon', 'cost' => 10]);

        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'cost']),
            collect(['Existing Item', 'weapon', 500]),
        ]));

        $item->refresh();

        $this->assertSame(500, $item->cost);
    }

    public function test_drop_location_relationship_resolves_by_name(): void
    {
        $location = $this->createLocation(['name' => 'Drop Spot', 'game_map_id' => $this->createGameMap()->id]);

        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'drop_location_id']),
            collect(['Quest Relic', 'quest', $location->name]),
        ]));

        $item = Item::where('name', 'Quest Relic')->first();

        $this->assertSame($location->id, $item->drop_location_id);
    }

    public function test_item_skill_relationship_resolves_by_name(): void
    {
        $itemSkill = $this->createItemSkill(['name' => 'Sample Skill']);

        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'item_skill_id']),
            collect(['Artifact Item', 'artifact', $itemSkill->name]),
        ]));

        $item = Item::where('name', 'Artifact Item')->first();

        $this->assertSame($itemSkill->id, $item->item_skill_id);
    }

    public function test_unlocks_class_and_skill_name_relationship_resolves(): void
    {
        $class = $this->createClass(['name' => 'Sample Class']);
        $skill = $this->createGameSkill(['name' => 'Sample Game Skill']);

        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'unlocks_class_id', 'skill_name']),
            collect(['Class Unlock Item', 'quest', $class->name, $skill->name]),
        ]));

        $item = Item::where('name', 'Class Unlock Item')->first();

        $this->assertSame($class->id, $item->unlocks_class_id);
    }

    public function test_invalid_unlocks_class_invalidates_the_entire_import(): void
    {
        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'unlocks_class_id']),
            collect(['Bad Class Item', 'quest', 'Does Not Exist Class']),
        ]));

        $this->assertNull(Item::where('name', 'Bad Class Item')->first());
    }

    public function test_invalid_drop_location_invalidates_the_entire_import(): void
    {
        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'drop_location_id']),
            collect(['Bad Location Item', 'quest', 'Does Not Exist Location']),
        ]));

        $this->assertNull(Item::where('name', 'Bad Location Item')->first());
    }

    public function test_missing_type_invalidates_the_entire_import(): void
    {
        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type']),
            collect(['No Type Item', null]),
        ]));

        $this->assertNull(Item::where('name', 'No Type Item')->first());
    }

    public function test_item_service_normalization_clears_effect_for_non_quest_types(): void
    {
        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'effect']),
            collect(['Non Quest Item', 'weapon', 'some-effect']),
        ]));

        $item = Item::where('name', 'Non Quest Item')->first();

        $this->assertNull($item->effect);
    }

    public function test_invalid_later_row_prevents_earlier_row_from_being_written(): void
    {
        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type', 'unlocks_class_id']),
            collect(['Valid Earlier Item', 'weapon', null]),
            collect(['Invalid Later Item', 'quest', 'Does Not Exist Class']),
        ]));

        $this->assertNull(Item::where('name', 'Valid Earlier Item')->first());
        $this->assertNull(Item::where('name', 'Invalid Later Item')->first());
    }

    public function test_blank_row_stops_processing_without_touching_rows_after_it(): void
    {
        (new ItemsSheet(new ItemService))->collection(collect([
            collect(['name', 'type']),
            collect(['Valid Before Blank', 'weapon']),
            collect([null, null]),
            collect(['Should Not Be Reached', 'weapon']),
        ]));

        $this->assertNotNull(Item::where('name', 'Valid Before Blank')->first());
        $this->assertNull(Item::where('name', 'Should Not Be Reached')->first());
    }
}
