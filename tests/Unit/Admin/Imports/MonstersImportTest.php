<?php

namespace Tests\Unit\Admin\Imports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Admin\Import\Monsters\MonstersImport;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class MonstersImportTest extends TestCase {
    use RefreshDatabase,
        CreateMonster,
        CreateItem,
        CreateGameMap,
        CreateGameSkill;

    public function testImport() {
        $this->createGameMap([
            'name' => 'Surface',
        ]);

        $this->createItem([
            'name' => 'Flask Of Fresh Air',
            'type' => 'quest'
        ]);

        $this->createMonster([
            'name' => 'Goblin'
        ]);

        $this->createGameSkill([
            'name' => 'Accuracy',
        ]);

        $this->createGameSkill([
            'name' => 'Dodge',
        ]);

        Excel::import(new MonstersImport, resource_path('data-imports/monsters.xlsx'));

        $this->assertTrue(true);
    }
}
