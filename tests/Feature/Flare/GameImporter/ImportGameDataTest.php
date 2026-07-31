<?php

namespace Tests\Feature\Flare\GameImporter;

use App\Admin\Import\LocationGems\LocationGemsImport;
use App\Admin\Import\LocationTemplates\LocationTemplatesImport;
use App\Admin\Import\MapGems\MapGemsImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportGameDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_game_data_can_re_import_location_templates(): void
    {
        Excel::fake();

        $this->artisan('import:game-data', ['dirName' => 'Location Templates']);

        Excel::assertImported(resource_path('data-imports').'/Location Templates/location_templates.xlsx', function (LocationTemplatesImport $import) {
            return $import instanceof LocationTemplatesImport;
        });
    }

    public function test_import_game_data_still_imports_world_gems(): void
    {
        Excel::fake();

        $this->artisan('import:game-data', ['dirName' => 'World Gems']);

        Excel::assertImported(resource_path('data-imports').'/World Gems/map-gems.xlsx', function (MapGemsImport $import) {
            return $import instanceof MapGemsImport;
        });
        Excel::assertImported(resource_path('data-imports').'/World Gems/location-gems.xlsx', function (LocationGemsImport $import) {
            return $import instanceof LocationGemsImport;
        });
    }
}
