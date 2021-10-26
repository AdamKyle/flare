<?php

namespace Tests\Unit\Admin\Exports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Exports\Kingdoms\KingdomsExport;

class KingdomsExportTest extends TestCase {
    use RefreshDatabase;

    public function testExport() {
        Excel::store(new KingdomsExport, 'test.xlsx');

        Storage::disk('local')->assertExists('test.xlsx');

        Storage::disk('local')->delete('test.xlsx');
    }
}
