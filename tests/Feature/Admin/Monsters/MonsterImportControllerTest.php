<?php

namespace Tests\Feature\Admin\Monsters;

use App\Flare\Models\Monster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonsterImportRow;
use Tests\Traits\CreateMonstersWorkbookFile;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class MonsterImportControllerTest extends TestCase
{
    use CreateGameMap, CreateMonsterImportRow, CreateMonstersWorkbookFile, CreateRole, CreateUser, RefreshDatabase;

    public function test_valid_workbook_imports_and_reports_success(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap(['name' => 'Import Map']);

        $file = $this->createMonstersWorkbookFile([
            $this->minimalMonsterImportRow(['name' => 'Imported Monster']),
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/monsters/import', [], [], ['monsters_import' => $file], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('monsters', ['name' => 'Imported Monster']);
    }

    public function test_invalid_workbook_reports_failure_and_writes_nothing(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $file = $this->createMonstersWorkbookFile([
            $this->minimalMonsterImportRow(['name' => 'Bad Monster', 'game_map_id' => 'Does Not Exist']),
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/monsters/import', [], [], ['monsters_import' => $file], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $this->assertArrayHasKey('message', json_decode($response->getContent(), true));
        $this->assertSame(0, Monster::count());
    }
}
