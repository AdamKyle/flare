<?php

namespace Tests\Feature\Flare\GameImporter;

use App\Admin\Import\LocationTemplates\LocationTemplatesImport;
use App\Admin\Import\MapGems\MapGemsImport;
use App\Admin\Import\LocationGems\LocationGemsImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportGameDataTest extends TestCase
{
    use RefreshDatabase;

    public function testImportGameDataCanReImportLocationTemplates(): void
    {
        Excel::fake();

        $this->artisan('import:game-data', ['dirName' => 'Location Templates']);

        Excel::assertImported(resource_path('data-imports').'/Location Templates/location_templates.xlsx', function (LocationTemplatesImport $import) {
            return $import instanceof LocationTemplatesImport;
        });
    }

    public function testImportGameDataStillImportsWorldGems(): void
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
