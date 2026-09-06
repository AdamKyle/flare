<?php

namespace Tests\Unit\Admin\Races\Imports\Sheets;

use App\Admin\Races\Imports\Sheets\RacesSheet;
use App\Flare\Models\GameRace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateRace;

class RacesSheetTest extends TestCase
{
    use CreateRace, RefreshDatabase;

    public function test_valid_workbook_row_creates_a_new_race(): void
    {
        (new RacesSheet)->collection(collect([
            collect(['name', 'description', 'image_path']),
            collect(['Imported Race', 'A new race.', 'race-images/imported.png']),
        ]));

        $race = GameRace::where('name', 'Imported Race')->first();

        $this->assertNotNull($race);
        $this->assertSame('A new race.', $race->description);
        $this->assertSame('race-images/imported.png', $race->image_path);
    }

    public function test_valid_workbook_row_updates_existing_race_by_name(): void
    {
        $race = $this->createRace(['name' => 'Existing Race', 'description' => 'Old description.']);

        (new RacesSheet)->collection(collect([
            collect(['name', 'description']),
            collect(['Existing Race', 'New description.']),
        ]));

        $race->refresh();

        $this->assertSame('New description.', $race->description);
    }

    public function test_old_modifier_workbook_columns_are_ignored(): void
    {
        (new RacesSheet)->collection(collect([
            collect(['name', 'str_mod', 'accuracy_mod']),
            collect(['Legacy Modifier Race', 5, 0.05]),
        ]));

        $race = GameRace::where('name', 'Legacy Modifier Race')->first();

        $this->assertNotNull($race);
    }

    public function test_old_workbook_without_description_or_image_remains_valid(): void
    {
        (new RacesSheet)->collection(collect([
            collect(['name']),
            collect(['Bare Race']),
        ]));

        $race = GameRace::where('name', 'Bare Race')->first();

        $this->assertNotNull($race);
        $this->assertNull($race->description);
        $this->assertNull($race->image_path);
    }

    public function test_updating_existing_race_preserves_description_when_workbook_omits_it(): void
    {
        $race = $this->createRace(['name' => 'Preserve Description Race', 'description' => 'Keep me.']);

        (new RacesSheet)->collection(collect([
            collect(['name']),
            collect(['Preserve Description Race']),
        ]));

        $race->refresh();

        $this->assertSame('Keep me.', $race->description);
    }

    public function test_updating_existing_race_preserves_image_path_when_workbook_omits_it(): void
    {
        $race = $this->createRace(['name' => 'Preserve Image Race', 'image_path' => 'race-images/keep.png']);

        (new RacesSheet)->collection(collect([
            collect(['name']),
            collect(['Preserve Image Race']),
        ]));

        $race->refresh();

        $this->assertSame('race-images/keep.png', $race->image_path);
    }

    public function test_non_string_required_race_name_fails(): void
    {
        $this->expectException(RuntimeException::class);

        (new RacesSheet)->collection(collect([
            collect(['name']),
            collect([12345]),
        ]));
    }

    public function test_duplicate_race_names_in_one_workbook_fail(): void
    {
        $this->expectException(RuntimeException::class);

        (new RacesSheet)->collection(collect([
            collect(['name']),
            collect(['Repeated Race']),
            collect(['Repeated Race']),
        ]));
    }

    public function test_populated_non_string_description_fails(): void
    {
        $this->expectException(RuntimeException::class);

        (new RacesSheet)->collection(collect([
            collect(['name', 'description']),
            collect(['Bad Description Race', 12345]),
        ]));
    }

    public function test_populated_non_string_image_path_fails(): void
    {
        $this->expectException(RuntimeException::class);

        (new RacesSheet)->collection(collect([
            collect(['name', 'image_path']),
            collect(['Bad Image Path Race', 12345]),
        ]));
    }

    public function test_blank_row_stops_processing_without_touching_rows_after_it(): void
    {
        (new RacesSheet)->collection(collect([
            collect(['name']),
            collect(['Valid Before Blank Race']),
            collect([null]),
            collect(['Should Not Be Reached Race']),
        ]));

        $this->assertNotNull(GameRace::where('name', 'Valid Before Blank Race')->first());
        $this->assertNull(GameRace::where('name', 'Should Not Be Reached Race')->first());
    }
}
