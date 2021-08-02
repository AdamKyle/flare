<?php

namespace Tests\Feature\Admin\Monsters;

use App\Admin\Exports\Monsters\MonstersExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Monster;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateMonster;
use Tests\Setup\Character\CharacterFactory;

class MonstersControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateMonster;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->createMonster();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testAdminCanSeeMonstersPage()
    {
        $this->actingAs($this->user)->visit(route('monsters.list'))->see('Monsters');
    }

    public function testNonAdminCannotSeeMonstersPage()
    {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->actingAs($user)->visit(route('game'))->visit(route('monsters.list'))->see('You don\'t have permission to view that.');
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('monsters.list'))->see(Monster::first()->name);
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('monsters.create'))->see('Create Monster');
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('monsters.monster', [
            'monster' => Monster::first()->id
        ]))->see(Monster::first()->name);
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('monster.edit', [
            'monster' => Monster::first()->id
        ]))->see(Monster::first()->name);
    }

    public function testPublishMonster() {
        $monster = $this->createMonster(['published' => false]);

        $this->actingAs($this->user)->post(route('monster.publish', ['monster' => $monster]));

        $this->assertTrue($monster->refresh()->published);
    }

    public function testCanSeeExportPage() {
        $this->actingAs($this->user)->visit(route('monsters.export'))->see('Export');
    }

    public function testCanExportMonsters() {
        Excel::fake();

        $this->actingAs($this->user)->post(route('monsters.export-data'));

        Excel::assertDownloaded('monsters.xlsx', function(MonstersExport $export) {
            return true;
        });
    }

    public function testCanSeeMonsterImportPage() {
        $this->actingAs($this->user)->visit(route('monsters.import'))->see('Import Monster Data');
    }

    public function testCanImportMonsters() {
        $this->actingAs($this->user)->post(route('monsters.import-data', [
            'monsters_import' => new UploadedFile(resource_path('data-imports/monsters.xlsx'), 'monsters.xlsx')
        ]));

        $this->assertTrue(Monster::all()->isNotEmpty());
    }
}
