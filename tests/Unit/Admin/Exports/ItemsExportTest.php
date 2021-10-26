<?php

namespace Tests\Unit\Admin\Exports;

use App\Admin\Exports\Items\ItemsExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Exports\Items\NpcsExport;

class ItemsExportTest extends TestCase
{
    use RefreshDatabase;

    public function testExport()
    {
        Excel::store(new ItemsExport(false), 'test.xlsx');

        Storage::disk('local')->assertExists('test.xlsx');

        Storage::disk('local')->delete('test.xlsx');
    }

    public function testExportWithAffixes()
    {
        Excel::store(new ItemsExport(true), 'test.xlsx');

        Storage::disk('local')->assertExists('test.xlsx');

        Storage::disk('local')->delete('test.xlsx');
    }
}
