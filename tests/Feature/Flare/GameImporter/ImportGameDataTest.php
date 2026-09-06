<?php

namespace Tests\Feature\Flare\GameImporter;

use App\Admin\Import\LocationTemplates\LocationTemplatesImport;
use App\Admin\LocationGems\Imports\LocationGemsImport;
use App\Admin\MapGems\Imports\MapGemsImport;
use App\Flare\GameImporter\Values\ExcelMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;

class ImportGameDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_game_data_can_re_import_location_templates(): void
    {
        Excel::shouldReceive('import')
            ->once()
            ->with(
                Mockery::type(LocationTemplatesImport::class),
                resource_path('data-imports').'/Location Templates/location_templates.xlsx'
            );

        $this->artisan('import:game-data', ['dirName' => 'Location Templates'])->assertExitCode(0);
    }

    public function test_import_game_data_still_imports_world_gems(): void
    {
        Excel::shouldReceive('import')
            ->once()
            ->ordered()
            ->with(
                Mockery::type(MapGemsImport::class),
                resource_path('data-imports').'/World Gems/map-gems.xlsx'
            );

        Excel::shouldReceive('import')
            ->once()
            ->ordered()
            ->with(
                Mockery::type(LocationGemsImport::class),
                resource_path('data-imports').'/World Gems/location-gems.xlsx'
            );

        $this->artisan('import:game-data', ['dirName' => 'World Gems'])->assertExitCode(0);
    }

    public function test_missing_world_gems_map_file_reports_error_and_does_not_import(): void
    {
        File::shouldReceive('exists')
            ->once()
            ->with(resource_path('data-imports').'/World Gems/map-gems.xlsx')
            ->andReturn(false);

        Excel::shouldReceive('import')->never();

        $this->artisan('import:game-data', ['dirName' => 'World Gems'])
            ->expectsOutputToContain('Missing file: resources/data-imports/World Gems/map-gems.xlsx')
            ->assertExitCode(0);
    }

    public function test_missing_world_gems_location_file_reports_error_and_does_not_import(): void
    {
        File::shouldReceive('exists')
            ->once()
            ->with(resource_path('data-imports').'/World Gems/map-gems.xlsx')
            ->andReturn(true);

        File::shouldReceive('exists')
            ->once()
            ->with(resource_path('data-imports').'/World Gems/location-gems.xlsx')
            ->andReturn(false);

        Excel::shouldReceive('import')->never();

        $this->artisan('import:game-data', ['dirName' => 'World Gems'])
            ->expectsOutputToContain('Missing file: resources/data-imports/World Gems/location-gems.xlsx')
            ->assertExitCode(0);
    }

    public function test_invalid_dir_name_reports_no_directory_error(): void
    {
        $this->artisan('import:game-data', ['dirName' => 'Not A Real Directory'])
            ->expectsOutputToContain('No directory in data-imports for: Not A Real Directory')
            ->assertExitCode(0);
    }

    public function test_normal_mapped_directory_delegates_each_file_to_excel_mapper(): void
    {
        $excelMapper = Mockery::mock(ExcelMapper::class);
        $excelMapper->shouldReceive('importFile')
            ->once()
            ->with('Skills', resource_path('data-imports').'/Skills/item-skills.xlsx', 0);
        $excelMapper->shouldReceive('importFile')
            ->once()
            ->with('Skills', resource_path('data-imports').'/Skills/skills.xlsx', 1);

        $this->app->instance(ExcelMapper::class, $excelMapper);

        $this->artisan('import:game-data', ['dirName' => 'Skills'])->assertExitCode(0);
    }

    public function test_core_imports_keeps_races_classes_class_specials_custom_order(): void
    {
        $excelMapper = Mockery::mock(ExcelMapper::class);
        $excelMapper->shouldReceive('importFile')
            ->once()
            ->ordered()
            ->with('Core Imports', resource_path('data-imports').'/Core Imports/game_races.xlsx', 0);
        $excelMapper->shouldReceive('importFile')
            ->once()
            ->ordered()
            ->with('Core Imports', resource_path('data-imports').'/Core Imports/game_classes.xlsx', 1);
        $excelMapper->shouldReceive('importFile')
            ->once()
            ->ordered()
            ->with('Core Imports', resource_path('data-imports').'/Core Imports/class-specials.xlsx', 2);

        $this->app->instance(ExcelMapper::class, $excelMapper);

        $this->artisan('import:game-data', ['dirName' => 'Core Imports'])->assertExitCode(0);
    }

    public function test_kingdom_files_remain_reversed(): void
    {
        Storage::fake('data-imports');
        Storage::disk('data-imports')->put('Kingdoms/a-first.xlsx', 'contents');
        Storage::disk('data-imports')->put('Kingdoms/b-second.xlsx', 'contents');

        $excelMapper = Mockery::mock(ExcelMapper::class);
        $excelMapper->shouldReceive('importFile')
            ->once()
            ->ordered()
            ->with('Kingdoms', resource_path('data-imports').'/Kingdoms/b-second.xlsx', 0);
        $excelMapper->shouldReceive('importFile')
            ->once()
            ->ordered()
            ->with('Kingdoms', resource_path('data-imports').'/Kingdoms/a-first.xlsx', 1);

        $this->app->instance(ExcelMapper::class, $excelMapper);

        $this->artisan('import:game-data', ['dirName' => 'Kingdoms'])->assertExitCode(0);
    }

    public function test_non_xlsx_files_are_ignored_by_fetch_behavior(): void
    {
        Storage::fake('data-imports');
        Storage::disk('data-imports')->put('Kingdoms/kingdoms.xlsx', 'contents');
        Storage::disk('data-imports')->put('Skills/skills.xlsx', 'contents');
        Storage::disk('data-imports')->put('Skills/notes.txt', 'contents');

        $excelMapper = Mockery::mock(ExcelMapper::class);
        $excelMapper->shouldReceive('importFile')
            ->once()
            ->with('Skills', resource_path('data-imports').'/Skills/skills.xlsx', 0);

        $this->app->instance(ExcelMapper::class, $excelMapper);

        $this->artisan('import:game-data', ['dirName' => 'Skills'])->assertExitCode(0);
    }
}
