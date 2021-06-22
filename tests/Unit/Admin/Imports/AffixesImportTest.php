<?php

namespace Tests\Unit\Admin\Imports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Import\Affixes\AffixesImport;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItemAffix;

class AffixesImportTest extends TestCase {
    use RefreshDatabase, CreateItemAffix, CreateGameSkill;

    public function testImport() {

        $this->createItemAffix([
            'name' => 'Archers Bane'
        ]);

        $this->createGameSkill([
            'name' => 'Weapon Crafting'
        ]);

        Excel::import(new AffixesImport, resource_path('data-imports/affixes.xlsx'));

        $this->assertTrue(true);
    }
}
