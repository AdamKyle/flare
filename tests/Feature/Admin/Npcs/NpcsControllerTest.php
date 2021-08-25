<?php

namespace Tests\Feature\Admin\Npcs;

use App\Admin\Exports\Npcs\NpcsExport;
use App\Flare\Values\NpcCommandTypes;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class NpcsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateNpc,
        CreateGameMap;

    private $user;

    private $npc;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->npc = $this->createNpc([
            'game_map_id' => $this->createGameMap()->id,
        ]);

        $this->npc->commands()->create([
            'npc_id' => $this->npc->id,
            'command' => 'Test',
            'command_type' => NpcCommandTypes::QUEST,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
        $this->npc  = null;
    }

    public function testCanSeeIndex() {
        $this->actingAs($this->user)->visit(route('npcs.index'))->see('NPC\'s');
    }

    public function testCanSeeShow() {
        $this->actingAs($this->user)->visit(route('npcs.show', [
            'npc' => $this->npc,
        ]))->see($this->npc->real_name);
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('npcs.create'))->see('Create NPC');
    }

    public function testEditController() {
        $this->actingAs($this->user)->visit(route('npcs.edit', [
            'npc' => $this->npc
        ]))->see($this->npc->name);
    }

    public function testCanSeeExportNpcs() {
        $this->actingAs($this->user)->visitRoute('npcs.export')->see('Export NPC Data');
    }

    public function testCanSeeImportNpcs() {
        $this->actingAs($this->user)->visitRoute('npcs.import')->see('Import NPC Data');
    }
}
