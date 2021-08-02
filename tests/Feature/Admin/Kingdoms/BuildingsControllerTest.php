<?php

namespace Tests\Feature\Admin\Kingdoms;

use App\Admin\Exports\Kingdoms\KingdomsExport;
use App\Flare\Models\GameBuilding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Maatwebsite\Excel\Facades\Excel;

class BuildingsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateGameBuilding;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->createGameBuilding();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testCanSeeIndex() {
        $this->actingAs($this->user)->visitRoute('buildings.list')->see('Buildings')->see('Test Building');
    }

    public function testCanSeeCreate() {
        $this->actingAs($this->user)->visitRoute('buildings.create')->see('Create building')->see('Building Details');
    }

    public function testCanSeeEdit() {
        $this->actingAs($this->user)->visitRoute('buildings.edit', [
            'building' => GameBuilding::first()->id,
        ])->see('Edit Building: Test Building')->see('Building Details');
    }

    public function testCanSeeShow() {
        $this->actingAs($this->user)->visitRoute('buildings.building', [
            'building' => GameBuilding::first()->id,
        ])->see('Test Building')->see('Base Details');
    }

    public function testCanExportBuildings() {
        Excel::fake();

        $this->actingAs($this->user)->visit(route('kingdoms.export'))->see('Export Kingdom Data');

        $this->actingAs($this->user)->post(route('kingdoms.export-data'));

        Excel::assertDownloaded('kingdoms.xlsx', function(KingdomsExport $export) {
            return true;
        });
    }

    public function testCanSeeImportPage() {
        $this->actingAs($this->user)->visit(route('kingdoms.import'))->see('Import Kingdom Data');
    }

    public function testCanImportKingdomData() {
        $this->actingAs($this->user)->post(route('kingdoms.import-data', [
            'kingdom_import' => new UploadedFile(resource_path('data-imports/kingdoms.xlsx'), 'kingdoms.xlsx')
        ]));

        $this->assertTrue(GameBuilding::all()->isNotEmpty());
    }
}
