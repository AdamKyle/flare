<?php

namespace Tests\Unit\Admin\Exports;

use App\Admin\Exports\PassiveSkills\PassiveSkillsExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PassiveSkillsExportTest extends TestCase {
    use RefreshDatabase;

    public function testExport() {
        Excel::store(new PassiveSkillsExport, 'test.xlsx');

        Storage::disk('local')->assertExists('test.xlsx');

        Storage::disk('local')->delete('test.xlsx');
    }
}
