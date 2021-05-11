<?php

namespace Tests\Feature;

use Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameSkill;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;

class InfoPageControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateAdventure,
        createClass,
        createRace,
        CreateGameSkill;

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
            'race' => 1,
        ])->see(GameRace::find(1)->name);
    }

    public function testViewClass() {
        $this->artisan('move:files');

        $this->createClass(['name' => 'Fighter']);

        $this->visitRoute('info.page.class', [
            'class' => 1,
        ])->see(GameClass::find(1)->name);
    }

    public function testViewSkill() {
        $this->artisan('move:files');

        $this->createGameSkill([
            'name' => 'Sample'
        ]);

        $this->visitRoute('info.page.skill', [
            'skill' => 1,
        ])->see(GameSkill::find(1)->name);
    }

    public function testViewAdventure() {
        $this->artisan('move:files');

        $adventure = $this->createNewAdventure();

        $this->visitRoute('info.page.adventure', [
            'adventure' => $adventure->id,
        ])->see($adventure->name);
    }
}
