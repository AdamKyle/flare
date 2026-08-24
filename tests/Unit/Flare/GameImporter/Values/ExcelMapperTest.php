<?php

namespace Tests\Unit\Flare\GameImporter\Values;

use App\Admin\Import\ItemSkills\ItemSkillsImport;
use App\Admin\Import\Raids\RaidsImport;
use App\Admin\Import\Skills\SkillsImport;
use App\Flare\GameImporter\Values\ExcelMapper;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;

class ExcelMapperTest extends TestCase
{
    public function test_scalar_mapping_uses_the_mapped_import_class(): void
    {
        $capturedImport = null;

        Excel::shouldReceive('import')
            ->once()
            ->with(Mockery::on(function ($import) use (&$capturedImport) {
                $capturedImport = $import;

                return true;
            }), '/path/raids.xlsx');

        (new ExcelMapper)->importFile('Raids', '/path/raids.xlsx', 0);

        $this->assertInstanceOf(RaidsImport::class, $capturedImport);
    }

    public function test_array_mapping_selects_the_class_by_index(): void
    {
        $capturedFirstImport = null;
        $capturedSecondImport = null;

        Excel::shouldReceive('import')
            ->once()
            ->with(Mockery::on(function ($import) use (&$capturedFirstImport) {
                $capturedFirstImport = $import;

                return true;
            }), '/path/item-skills.xlsx');

        Excel::shouldReceive('import')
            ->once()
            ->with(Mockery::on(function ($import) use (&$capturedSecondImport) {
                $capturedSecondImport = $import;

                return true;
            }), '/path/skills.xlsx');

        (new ExcelMapper)->importFile('Skills', '/path/item-skills.xlsx', 0);
        (new ExcelMapper)->importFile('Skills', '/path/skills.xlsx', 1);

        $this->assertInstanceOf(ItemSkillsImport::class, $capturedFirstImport);
        $this->assertInstanceOf(SkillsImport::class, $capturedSecondImport);
    }
}
