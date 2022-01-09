<?php

namespace Tests\Unit\Admin\Imports;

use App\Admin\Import\PassiveSkills\PassiveSkillsImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PassiveSkillsImportTest extends TestCase {
    use RefreshDatabase;

    public function testImport() {
        Excel::import(new PassiveSkillsImport(), resource_path('data-imports/passive_skills.xlsx'));

        $this->assertTrue(true);
    }
}
