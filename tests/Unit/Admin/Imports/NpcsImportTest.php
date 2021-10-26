<?php

namespace Tests\Unit\Admin\Imports;

use App\Admin\Import\Items\ItemsImport;
use App\Admin\Import\Npcs\NpcsImport;
use App\Admin\Requests\NpcsImportRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Import\Items\QuestsImport;

class NpcsImportTest extends TestCase {
    use RefreshDatabase;

    public function testImport() {
        Excel::import(new NpcsImport, resource_path('data-imports/npcs.xlsx'));

        $this->assertTrue(true);
    }
}
