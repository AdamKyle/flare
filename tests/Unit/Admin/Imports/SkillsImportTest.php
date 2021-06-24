<?php

namespace Tests\Unit\Admin\Imports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Import\Skills\SkillsImport;

class SkillsImportTest extends TestCase {
    use RefreshDatabase;

    public function testImport() {
        Excel::import(new SkillsImport(), resource_path('data-imports/skills.xlsx'));

        $this->assertTrue(true);
    }
}
