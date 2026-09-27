<?php

namespace Tests\Feature\Admin\Kingdoms;

use App\Admin\Jobs\AssignNewKingdomBuildingsJob;
use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Admin\Kingdoms\Exceptions\KingdomWorkbookException;
use App\Admin\Kingdoms\Imports\KingdomsImport;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class KingdomImportControllerTest extends TestCase
{
    use CreateGameBuilding, CreateGameBuildingUnit, CreateGameUnit, CreateRole, CreateUser, RefreshDatabase;

    public function test_import_rejects_non_admin_request(): void
    {
        $file = UploadedFile::fake()->create('kingdoms.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->createUser())->post('/api/admin/kingdoms/import', ['kingdom_import' => $file], ['Accept' => 'application/json']);

        $response->assertForbidden();
    }

    public function test_import_synchronizes_the_workbook_relationships(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $barracks = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true, 'units_per_level' => 5]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);
        $this->createGameBuildingUnit(['game_building_id' => $barracks->id, 'game_unit_id' => $spearmen->id, 'required_level' => 1]);
        $file = UploadedFile::fake()->create('kingdoms.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Excel::shouldReceive('import')->once()->andReturnUsing(function (KingdomsImport $import) use ($barracks): mixed {
            $sheets = $import->sheets();

            $sheets[0]->collection(collect([
                collect(['id', 'name', 'trains_units', 'units_per_level']),
                collect([$barracks->id, 'Barracks', true, 5]),
            ]));
            $sheets[1]->collection(collect([collect(['id', 'name'])]));
            $sheets[2]->collection(collect([
                collect(['id', 'Building', 'Unit', 'Required Level']),
                collect([null, 'Barracks', 'Archers', 1]),
            ]));

            return Excel::getFacadeRoot();
        });

        $response = $this->actingAs($admin)->post('/api/admin/kingdoms/import', ['kingdom_import' => $file]);

        $response->assertOk()->assertJson(['message' => 'Kingdom data imported successfully.']);
        $this->assertSame([$archers->id], GameBuildingUnit::where('game_building_id', $barracks->id)->pluck('game_unit_id')->all());
    }

    public function test_successful_import_assigns_buildings_to_player_kingdoms(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $building = $this->createGameBuilding(['name' => 'Barracks']);
        $file = UploadedFile::fake()->create('kingdoms.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Excel::shouldReceive('import')->once()->andReturn(Excel::getFacadeRoot());

        $this->actingAs($admin)->post('/api/admin/kingdoms/import', ['kingdom_import' => $file])->assertOk();

        Queue::assertPushed(AssignNewKingdomBuildingsJob::class, function (AssignNewKingdomBuildingsJob $job) use ($building): bool {
            return $job->gameBuilding->id === $building->id;
        });
    }

    public function test_successful_import_refreshes_existing_player_kingdom_buildings(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $building = $this->createGameBuilding(['name' => 'Walls']);
        $file = UploadedFile::fake()->create('kingdoms.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Excel::shouldReceive('import')->once()->andReturn(Excel::getFacadeRoot());

        $this->actingAs($admin)->post('/api/admin/kingdoms/import', ['kingdom_import' => $file])->assertOk();

        Queue::assertPushed(UpdateKingdomBuildings::class, function (UpdateKingdomBuildings $job) use ($building): bool {
            return $job->gameBuilding->id === $building->id;
        });
    }

    public function test_failed_import_leaves_no_partial_definitions_or_relationships(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $barracks = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $this->createGameBuildingUnit(['game_building_id' => $barracks->id, 'game_unit_id' => $spearmen->id, 'required_level' => 1]);
        $file = UploadedFile::fake()->create('kingdoms.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Excel::shouldReceive('import')->once()->andReturnUsing(function (KingdomsImport $import) use ($barracks): mixed {
            $sheets = $import->sheets();

            $sheets[0]->collection(collect([
                collect(['id', 'name', 'description', 'max_level', 'base_durability', 'base_defence', 'required_population', 'trains_units']),
                collect([$barracks->id, 'Barracks', 'Trains soldiers.', 30, 100, 100, 10, true]),
                collect([null, 'Watch Tower', 'Watches.', 10, 100, 100, 10, false]),
            ]));
            $sheets[1]->collection(collect([collect(['id', 'name'])]));
            $sheets[2]->collection(collect([
                collect(['id', 'Building', 'Unit', 'Required Level']),
                collect([null, 'Barracks', 'Ghost Riders', 1]),
            ]));

            return Excel::getFacadeRoot();
        });

        $response = $this->actingAs($admin)->post('/api/admin/kingdoms/import', ['kingdom_import' => $file]);

        $response->assertUnprocessable()->assertJson(['message' => 'The Building Units sheet references an unknown Unit: Ghost Riders.']);
        $this->assertNull(GameBuilding::where('name', 'Watch Tower')->first());
        $this->assertSame([$spearmen->id], GameBuildingUnit::where('game_building_id', $barracks->id)->pluck('game_unit_id')->all());
    }

    public function test_failed_import_does_not_propagate_buildings_to_player_kingdoms(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('kingdoms.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Excel::shouldReceive('import')->once()->andThrow(new KingdomWorkbookException('Invalid workbook.'));

        $this->actingAs($admin)->post('/api/admin/kingdoms/import', ['kingdom_import' => $file])->assertUnprocessable();

        Queue::assertNotPushed(AssignNewKingdomBuildingsJob::class);
        Queue::assertNotPushed(UpdateKingdomBuildings::class);
    }
}
