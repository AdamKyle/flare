<?php

namespace Tests\Feature\Admin\Quests;

use App\Flare\Models\Quest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuestsWorkbookFile;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class QuestImportControllerTest extends TestCase
{
    use CreateNpc, CreateQuestsWorkbookFile, CreateRole, CreateUser, RefreshDatabase;

    public function test_valid_workbook_imports_and_reports_success(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $npc = $this->createNpc();

        $file = $this->createQuestsWorkbookFile([
            ['name' => 'Imported Quest', 'npc_id' => $npc->real_name],
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/quests/import', [], [], ['quests_import' => $file], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quests', ['name' => 'Imported Quest']);
    }

    public function test_invalid_workbook_reports_failure_and_writes_nothing(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $file = $this->createQuestsWorkbookFile([
            ['name' => 'Bad Quest', 'npc_id' => 'Does Not Exist'],
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/quests/import', [], [], ['quests_import' => $file], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $this->assertArrayHasKey('message', json_decode($response->getContent(), true));
        $this->assertSame(0, Quest::count());
    }
}
