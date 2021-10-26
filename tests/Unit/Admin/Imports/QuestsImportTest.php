<?php

namespace Tests\Unit\Admin\Imports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Import\Quests\QuestsImport;
use Tests\TestCase;

class QuestsImportTest extends TestCase {
    use RefreshDatabase;

    public function testImport() {
        Excel::import(new QuestsImport, resource_path('data-imports/quests.xlsx'));

        $this->assertTrue(true);
    }
}
