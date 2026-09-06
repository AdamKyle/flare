<?php

namespace Tests\Unit\Admin\Excel;

use App\Admin\Classes\Exports\ClassesExport;
use App\Admin\Classes\Imports\ClassesImport;
use App\Admin\ClassMasteries\Exports\ClassMasteriesExport;
use App\Admin\ClassMasteries\Imports\ClassMasteriesImport;
use App\Admin\GameMaps\Exports\GameMapsExport;
use App\Admin\GameMaps\Imports\GameMapsImport;
use App\Admin\Items\Exports\ItemsExport;
use App\Admin\Items\Imports\ItemsImport;
use App\Admin\LocationGems\Exports\LocationGemsExport;
use App\Admin\LocationGems\Imports\LocationGemsImport;
use App\Admin\Locations\Exports\LocationsExport;
use App\Admin\Locations\Imports\LocationsImport;
use App\Admin\MapGems\Exports\MapGemsExport;
use App\Admin\MapGems\Imports\MapGemsImport;
use App\Admin\Monsters\Exports\MonstersExport;
use App\Admin\Monsters\Imports\MonstersImport;
use App\Admin\Npcs\Exports\NpcsExport;
use App\Admin\Npcs\Imports\NpcsImport;
use App\Admin\Quests\Exports\QuestsExport;
use App\Admin\Quests\Imports\QuestsImport;
use App\Admin\Races\Exports\RacesExport;
use App\Admin\Races\Imports\RacesImport;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Import;
use Tests\TestCase;

class AdminExcelContractTest extends TestCase
{
    public function test_modern_admin_exports_implement_the_maatwebsite_export_contract(): void
    {
        $this->assertInstanceOf(Export::class, new GameMapsExport, GameMapsExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new LocationsExport, LocationsExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new NpcsExport, NpcsExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new MonstersExport, MonstersExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new ItemsExport, ItemsExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new QuestsExport, QuestsExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new ClassesExport, ClassesExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new RacesExport, RacesExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new ClassMasteriesExport, ClassMasteriesExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new MapGemsExport, MapGemsExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
        $this->assertInstanceOf(Export::class, new LocationGemsExport, LocationGemsExport::class.' must implement Maatwebsite\Excel\Concerns\Export.');
    }

    public function test_modern_admin_imports_implement_the_maatwebsite_import_contract(): void
    {
        $this->assertInstanceOf(Import::class, new GameMapsImport, GameMapsImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new LocationsImport, LocationsImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new NpcsImport, NpcsImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new MonstersImport, MonstersImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new ItemsImport, ItemsImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new QuestsImport, QuestsImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new ClassesImport, ClassesImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new RacesImport, RacesImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new ClassMasteriesImport, ClassMasteriesImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new MapGemsImport, MapGemsImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
        $this->assertInstanceOf(Import::class, new LocationGemsImport, LocationGemsImport::class.' must implement Maatwebsite\Excel\Concerns\Import.');
    }
}
