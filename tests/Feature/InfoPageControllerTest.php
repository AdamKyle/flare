<?php

namespace Tests\Feature;

use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameSkill;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;

class InfoPageControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateAdventure,
        createClass,
        createRace,
        CreateGameSkill,
        CreateGameMap,
        CreateLocation,
        CreateGameBuilding,
        CreateGameUnit,
        CreateGameBuildingUnit,
        CreateItem,
        CreateItemAffix;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        $config = app('config');

        $files = Storage::disk('info')->allFiles();

        Storage::disk('info')->delete($files);

        parent::tearDown();

        app()->instance('config', $config);
    }

    public function testCanSeeInfoPage() {
        $this->artisan('move:files');

        $this->visitRoute('info.page', [
            'pageName' => 'home'
        ])->see('Planes Of Tlessa');
    }

    public function testCanSeePageWithLiveWireTable() {
        $this->artisan('move:files');

        $this->createClass(['name' => 'Fighter']);
        $this->createRace(['name' => 'Human']);

        $this->visitRoute('info.page', [
            'pageName' => 'races-and-classes'
        ])->see('Human');
    }

    public function test404ForNoFiles() {
        $response = $this->call('GET', route('info.page', [
            'pageName' => 'home'
        ]));

        $response->assertStatus(404);
    }

    public function test404ForInvalidPage() {
        $this->artisan('move:files');

        config(['info.home' => null]);

        $response = $this->call('GET', route('info.page', [
            'pageName' => 'home'
        ]));

        $response->assertStatus(404);
    }

    public function testViewRace() {
        $this->artisan('move:files');

        $this->createRace(['name' => 'Human']);

        $this->visitRoute('info.page.race', [
            'race' => GameRace::first()->id,
        ])->see(GameRace::first()->name);
    }

    public function testViewClass() {
        $this->artisan('move:files');

        $this->createClass(['name' => 'Fighter']);

        $this->visitRoute('info.page.class', [
            'class' => GameClass::first()->id,
        ])->see(GameClass::first()->name);
    }

    public function testViewSkill() {
        $this->artisan('move:files');

        $this->createGameSkill([
            'name' => 'Sample'
        ]);

        $this->visitRoute('info.page.skill', [
            'skill' => GameSkill::first()->id,
        ])->see(GameSkill::first()->name);
    }

    public function testViewMonster() {
        $this->artisan('move:files');

        $this->createMonster([
            'name' => 'Sample'
        ]);

        $this->visitRoute('info.page.monster', [
            'monster' => Monster::first()->id,
        ])->see(Monster::first()->name);
    }

    public function testViewLocation() {
        $this->artisan('move:files');

        $map = $this->createGameMap();

        $this->createLocation([
            'name' => 'Sample',
            'game_map_id' => $map->id,
            'description' => 'sample',
            'x' => 16,
            'y' => 16,
        ]);

        $this->visitRoute('info.page.location', [
            'location' => Location::first()->id,
        ])->see(Location::first()->name);
    }

    public function testViewAdventure() {
        $this->artisan('move:files');

        $adventure = $this->createNewAdventure();

        $this->visitRoute('info.page.adventure', [
            'adventure' => $adventure->id,
        ])->see($adventure->name);
    }

    public function testViewItem() {
        $this->artisan('move:files');

        $item = $this->createItem(['name' => 'Sample']);

        $this->visitRoute('info.page.item', [
            'item' => $item->id,
        ])->see($item->name);
    }

    public function testViewAffix() {
        $this->artisan('move:files');

        $itemAffix = $this->createItemAffix(['name' => 'Sample']);

        $this->visitRoute('info.page.affix', [
            'affix' => $itemAffix->id,
        ])->see($itemAffix->name);
    }

    public function testViewGameUnit() {
        $this->artisan('move:files');

        $gameBuilding = $this->createGameBuilding();
        $gameUnit     = $this->createGameUnit([
            'name' => 'Sample'
        ]);

        $this->createGameBuildingUnit([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id'     => $gameUnit->id,
        ]);

        $this->visitRoute('info.page.unit', [
            'unit' => $gameUnit->id,
        ])->see($gameUnit->name);
    }
}
