<?php

namespace Tests\Unit\Admin\Imports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Import\Affixes\AffixesImport;

class AffixesImportTest extends TestCase {
    use RefreshDatabase;

    public function testImport() {
        Excel::import(new AffixesImport, resource_path('data-imports/affixes.xlsx'));

        $this->assertTrue(true);
    }
}
