<?php

namespace Tests\Feature\Admin\Maps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Flare\Models\GameMap;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\CharacterSetup;

class MapsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole;

    private $user;

    public function setUp(): void {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin([], $role);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->user = null;
    }

    public function testAdminCanSeeMapsPage() {
        $this->actingAs($this->user)->visit(route('maps'))->see('Maps');
    }

    public function testNonAdminCannotSeeMapsPage() {
        $user = $this->createUser();

        (new CharacterSetup)->setupCharacter($user)->getCharacter();

        $this->actingAs($user)->visit(route('game'))->visit(route('maps'))->dontSee('Maps')->see('You don\'t have permission to view that.');
    }

    public function testAdminCanSeeUploadMapPage() {
        $this->actingAs($this->user)->visit(route('maps.upload'))->see('Upload Map');
    }

    public function testUploadImage() {
        $this->actingAs($this->user)
             ->visit(route('maps.upload'))
             ->type('Surface', 'name')
             ->select('yes', 'default')
             ->attach(UploadedFile::fake()->image('avatar.jpg'), 'map')
             ->press('Submit')
             ->see('Surface');

        $this->assertTrue(GameMap::all()->isNotEmpty());
        $this->assertNotNull(GameMap::where('default', true)->first());

        Storage::disk('maps')->deleteDirectory('Surface/');
    }

    public function testCannotUploadImageWhenValuesMissing() {
        $this->actingAs($this->user)
             ->visit(route('maps.upload'))
             ->press('Submit')
             ->see('Map name is required.')
             ->see('Map upload is required.');
    }

    public function testNonAdminCannotSeeUploadMapPage() {
        $user = $this->createUser();

        (new CharacterSetup)->setupCharacter($user)->getCharacter();

        $this->actingAs($user)->visit(route('game'))->visit(route('maps.upload'))->dontSee('Upload Map')->see('You don\'t have permission to view that.');
    }
}
