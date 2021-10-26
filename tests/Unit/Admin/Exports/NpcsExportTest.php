<?php

namespace Tests\Unit\Admin\Exports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Exports\Npcs\NpcsExport;

class NpcsExportTest extends TestCase {
    use RefreshDatabase;

    public function testExport() {
        Excel::store(new NpcsExport, 'test.xlsx');

        Storage::disk('local')->assertExists('test.xlsx');

        Storage::disk('local')->delete('test.xlsx');
    }
}
