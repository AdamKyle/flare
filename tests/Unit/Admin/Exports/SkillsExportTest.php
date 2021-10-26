<?php

namespace Tests\Unit\Admin\Exports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Exports\Skills\SkillsExport;

class SkillsExportTest extends TestCase {
    use RefreshDatabase;

    public function testExport() {
        Excel::store(new SkillsExport, 'test.xlsx');

        Storage::disk('local')->assertExists('test.xlsx');

        Storage::disk('local')->delete('test.xlsx');
    }
}
