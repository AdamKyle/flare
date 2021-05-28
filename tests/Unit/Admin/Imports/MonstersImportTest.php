<?php

namespace Tests\Unit\Admin\Imports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Import\Monsters\MonstersImport;

class MonstersImportTest extends TestCase {
    use RefreshDatabase;

    public function testImport() {
        Excel::import(new MonstersImport, resource_path('data-imports/monsters.xlsx'));

        $this->assertTrue(true);
    }
}
