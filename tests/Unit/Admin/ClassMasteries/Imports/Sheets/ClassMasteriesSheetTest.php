<?php

namespace Tests\Unit\Admin\ClassMasteries\Imports\Sheets;

use App\Admin\ClassMasteries\Imports\Sheets\ClassMasteriesSheet;
use App\Flare\Models\GameClassSpecial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;

class ClassMasteriesSheetTest extends TestCase
{
    use CreateClass, CreateGameClassSpecial, RefreshDatabase;

    public function test_valid_workbook_row_creates_a_new_class_mastery(): void
    {
        $gameClass = $this->createClass(['name' => 'Import Class']);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id', 'requires_class_rank_level']),
            collect(['Imported Mastery', $gameClass->id, 5]),
        ]));

        $mastery = GameClassSpecial::where('name', 'Imported Mastery')->first();

        $this->assertNotNull($mastery);
        $this->assertSame($gameClass->id, $mastery->game_class_id);
    }

    public function test_valid_workbook_row_updates_existing_class_mastery_by_name(): void
    {
        $gameClass = $this->createClass(['name' => 'Update Class']);
        $mastery = $this->createGameClassSpecial([
            'name' => 'Existing Mastery',
            'game_class_id' => $gameClass->id,
            'requires_class_rank_level' => 1,
        ]);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id', 'requires_class_rank_level']),
            collect(['Existing Mastery', $gameClass->id, 25]),
        ]));

        $mastery->refresh();

        $this->assertSame(25, $mastery->requires_class_rank_level);
    }

    public function test_unknown_game_class_id_fails_the_import(): void
    {
        $this->expectException(RuntimeException::class);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id']),
            collect(['Bad Class Mastery', 999999]),
        ]));
    }

    public function test_rank_below_minimum_fails_the_import(): void
    {
        $gameClass = $this->createClass(['name' => 'Below Minimum Class']);
        $this->expectException(RuntimeException::class);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id', 'requires_class_rank_level']),
            collect(['Below Minimum Mastery', $gameClass->id, -1]),
        ]));
    }

    public function test_rank_above_maximum_fails_the_import(): void
    {
        $gameClass = $this->createClass(['name' => 'Above Maximum Class']);
        $this->expectException(RuntimeException::class);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id', 'requires_class_rank_level']),
            collect(['Above Maximum Mastery', $gameClass->id, 101]),
        ]));
    }

    public function test_invalid_attack_type_fails_the_import(): void
    {
        $gameClass = $this->createClass(['name' => 'Invalid Attack Type Class']);
        $this->expectException(RuntimeException::class);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id', 'attack_type_required']),
            collect(['Invalid Attack Type Mastery', $gameClass->id, 'not-a-real-attack-type']),
        ]));
    }

    public function test_invalid_populated_integer_attack_field_fails_the_import(): void
    {
        $gameClass = $this->createClass(['name' => 'Invalid Integer Field Class']);
        $this->expectException(RuntimeException::class);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id', 'specialty_damage']),
            collect(['Invalid Integer Field Mastery', $gameClass->id, 'not-a-number']),
        ]));
    }

    public function test_invalid_populated_numeric_modifier_fails_the_import(): void
    {
        $gameClass = $this->createClass(['name' => 'Invalid Numeric Modifier Class']);
        $this->expectException(RuntimeException::class);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id', 'base_damage_mod']),
            collect(['Invalid Numeric Modifier Mastery', $gameClass->id, 'not-a-number']),
        ]));
    }

    public function test_non_string_required_mastery_name_fails_the_import(): void
    {
        $this->expectException(RuntimeException::class);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id']),
            collect([12345, 1]),
        ]));
    }

    public function test_updating_existing_mastery_preserves_description_when_workbook_omits_it(): void
    {
        $gameClass = $this->createClass(['name' => 'Preserve Description Class']);
        $mastery = $this->createGameClassSpecial([
            'name' => 'Preserve Description Mastery',
            'game_class_id' => $gameClass->id,
            'description' => 'Keep me.',
        ]);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id']),
            collect(['Preserve Description Mastery', $gameClass->id]),
        ]));

        $mastery->refresh();

        $this->assertSame('Keep me.', $mastery->description);
    }

    public function test_new_mastery_never_persists_null_description(): void
    {
        $gameClass = $this->createClass(['name' => 'No Description Class']);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id']),
            collect(['No Description Mastery', $gameClass->id]),
        ]));

        $mastery = GameClassSpecial::where('name', 'No Description Mastery')->first();

        $this->assertNotNull($mastery);
        $this->assertSame('', $mastery->description);
    }

    public function test_blank_row_stops_processing_without_touching_rows_after_it(): void
    {
        $gameClass = $this->createClass(['name' => 'Blank Stop Class']);

        (new ClassMasteriesSheet)->collection(collect([
            collect(['name', 'game_class_id']),
            collect(['Valid Before Blank Mastery', $gameClass->id]),
            collect([null, null]),
            collect(['Should Not Be Reached Mastery', $gameClass->id]),
        ]));

        $this->assertNotNull(GameClassSpecial::where('name', 'Valid Before Blank Mastery')->first());
        $this->assertNull(GameClassSpecial::where('name', 'Should Not Be Reached Mastery')->first());
    }
}
