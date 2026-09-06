<?php

namespace Tests\Unit\Admin\Classes\Imports\Sheets;

use App\Admin\Classes\Imports\Sheets\ClassesSheet;
use App\Admin\Classes\Services\ClassService;
use App\Flare\Models\GameClass;
use App\Game\ClassRanks\Values\ClassRankValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class ClassesSheetTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    public function test_valid_workbook_row_creates_a_new_class(): void
    {
        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect(['Imported Class', 'str', 'dex']),
        ]));

        $gameClass = GameClass::where('name', 'Imported Class')->first();

        $this->assertNotNull($gameClass);
        $this->assertSame('str', $gameClass->damage_stat);
    }

    public function test_valid_workbook_row_updates_existing_class_by_name(): void
    {
        $gameClass = $this->createClass(['name' => 'Existing Class', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect(['Existing Class', 'int', 'chr']),
        ]));

        $gameClass->refresh();

        $this->assertSame('int', $gameClass->damage_stat);
    }

    public function test_prerequisite_workbook_names_resolve_to_ids(): void
    {
        $primary = $this->createClass(['name' => 'Primary Prereq']);
        $secondary = $this->createClass(['name' => 'Secondary Prereq']);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id', 'secondary_required_class_id', 'primary_required_class_level', 'secondary_required_class_level']),
            collect(['Special Class', 'str', 'dex', $primary->name, $secondary->name, 10, 20]),
        ]));

        $gameClass = GameClass::where('name', 'Special Class')->first();

        $this->assertSame($primary->id, $gameClass->primary_required_class_id);
        $this->assertSame($secondary->id, $gameClass->secondary_required_class_id);
    }

    public function test_invalid_prerequisite_workbook_name_fails_rather_than_silently_skipping(): void
    {
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id']),
            collect(['Bad Prereq Class', 'str', 'dex', 'Does Not Exist']),
        ]));
    }

    public function test_incomplete_unlock_requirement_set_fails(): void
    {
        $primary = $this->createClass(['name' => 'Incomplete Primary Prereq']);
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id', 'primary_required_class_level']),
            collect(['Incomplete Unlock Class', 'str', 'dex', $primary->name, 10]),
        ]));
    }

    public function test_same_primary_and_secondary_prerequisite_class_fails(): void
    {
        $prereq = $this->createClass(['name' => 'Same Prereq']);
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id', 'secondary_required_class_id', 'primary_required_class_level', 'secondary_required_class_level']),
            collect(['Same Prereq Class', 'str', 'dex', $prereq->name, $prereq->name, 10, 20]),
        ]));
    }

    public function test_existing_class_cannot_be_updated_to_require_itself(): void
    {
        $gameClass = $this->createClass(['name' => 'Self Reference Class']);
        $other = $this->createClass(['name' => 'Self Reference Other Class']);
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id', 'secondary_required_class_id', 'primary_required_class_level', 'secondary_required_class_level']),
            collect([$gameClass->name, 'str', 'dex', $gameClass->name, $other->name, 10, 20]),
        ]));
    }

    public function test_prerequisite_rank_below_minimum_fails(): void
    {
        $primary = $this->createClass(['name' => 'Below Min Primary']);
        $secondary = $this->createClass(['name' => 'Below Min Secondary']);
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id', 'secondary_required_class_id', 'primary_required_class_level', 'secondary_required_class_level']),
            collect(['Below Min Rank Class', 'str', 'dex', $primary->name, $secondary->name, 0, 20]),
        ]));
    }

    public function test_prerequisite_rank_above_maximum_fails(): void
    {
        $primary = $this->createClass(['name' => 'Above Max Primary']);
        $secondary = $this->createClass(['name' => 'Above Max Secondary']);
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id', 'secondary_required_class_id', 'primary_required_class_level', 'secondary_required_class_level']),
            collect(['Above Max Rank Class', 'str', 'dex', $primary->name, $secondary->name, ClassRankValue::MAX_LEVEL + 1, 20]),
        ]));
    }

    public function test_invalid_damage_stat_fails(): void
    {
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect(['Invalid Damage Stat Class', 'not-a-real-stat', 'dex']),
        ]));
    }

    public function test_invalid_to_hit_stat_fails(): void
    {
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect(['Invalid To Hit Stat Class', 'str', 'not-a-real-stat']),
        ]));
    }

    public function test_non_string_required_class_name_fails(): void
    {
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect([12345, 'str', 'dex']),
        ]));
    }

    public function test_unresolved_prerequisite_not_present_in_database_or_workbook_fails(): void
    {
        $secondary = $this->createClass(['name' => 'Known Secondary Prereq']);
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id', 'secondary_required_class_id', 'primary_required_class_level', 'secondary_required_class_level']),
            collect(['Unresolved Prereq Class', 'str', 'dex', 'Nowhere To Be Found', $secondary->name, 10, 20]),
        ]));
    }

    public function test_fresh_database_same_workbook_prerequisite_classes_resolve_correctly(): void
    {
        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat', 'primary_required_class_id', 'secondary_required_class_id', 'primary_required_class_level', 'secondary_required_class_level']),
            collect(['Prisoner', 'str', 'dex', 'Fighter', 'Thief', 15, 25]),
            collect(['Fighter', 'str', 'dex', null, null, null, null]),
            collect(['Thief', 'agi', 'dex', null, null, null, null]),
        ]));

        $fighter = GameClass::where('name', 'Fighter')->first();
        $thief = GameClass::where('name', 'Thief')->first();
        $prisoner = GameClass::where('name', 'Prisoner')->first();

        $this->assertNotNull($fighter);
        $this->assertNotNull($thief);
        $this->assertSame($fighter->id, $prisoner->primary_required_class_id);
        $this->assertSame($thief->id, $prisoner->secondary_required_class_id);
        $this->assertSame(15, $prisoner->primary_required_class_level);
        $this->assertSame(25, $prisoner->secondary_required_class_level);
    }

    public function test_duplicate_name_rows_in_one_workbook_fail_the_import(): void
    {
        $this->expectException(RuntimeException::class);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect(['Repeated Class', 'str', 'dex']),
            collect(['Repeated Class', 'int', 'chr']),
        ]));
    }

    public function test_old_import_without_description_remains_valid(): void
    {
        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect(['No Description Class', 'str', 'dex']),
        ]));

        $gameClass = GameClass::where('name', 'No Description Class')->first();

        $this->assertNotNull($gameClass);
        $this->assertNull($gameClass->description);
    }

    public function test_updating_existing_class_from_old_workbook_preserves_existing_description(): void
    {
        $gameClass = $this->createClass([
            'name' => 'Preserve Description Class',
            'damage_stat' => 'str',
            'to_hit_stat' => 'dex',
            'description' => 'Original description.',
        ]);

        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect(['Preserve Description Class', 'int', 'chr']),
        ]));

        $gameClass->refresh();

        $this->assertSame('Original description.', $gameClass->description);
    }

    public function test_blank_row_stops_processing_without_touching_rows_after_it(): void
    {
        (new ClassesSheet(new ClassService))->collection(collect([
            collect(['name', 'damage_stat', 'to_hit_stat']),
            collect(['Valid Before Blank', 'str', 'dex']),
            collect([null, null, null]),
            collect(['Should Not Be Reached', 'str', 'dex']),
        ]));

        $this->assertNotNull(GameClass::where('name', 'Valid Before Blank')->first());
        $this->assertNull(GameClass::where('name', 'Should Not Be Reached')->first());
    }
}
