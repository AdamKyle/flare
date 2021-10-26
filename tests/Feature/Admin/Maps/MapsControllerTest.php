<?php

namespace Tests\Feature\Admin\Maps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Flare\Models\GameMap;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;

class MapsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateGameMap;

    private $user;

    public function setUp(): void {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->user = null;
    }

    public function testAdminCanSeeMapsPage() {
        $this->actingAs($this->user)->visit(route('maps'))->see('Maps');
    }

    public function testNonAdminCannotSeeMapsPage() {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

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
             ->attach(UploadedFile::fake()->image('avatar.jpeg'), 'map')
             ->type('#ffffff', 'kingdom_color')
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
             ->see('Map upload is required.')
             ->see('Kingdom color is required.');
    }

    public function testNonAdminCannotSeeUploadMapPage() {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->actingAs($user)->visit(route('game'))->visit(route('maps.upload'))->dontSee('Upload Map')->see('You don\'t have permission to view that.');
    }

    public function testViewCreateMapBonusesForm() {
        $gameMap = $this->createGameMap();

        $this->actingAs($this->user)
            ->visit(route('map.bonuses', ['gameMap' => $gameMap]))
            ->see('Map Bonuses');
    }

    public function testSaveMapBonuses() {
        $gameMap = $this->createGameMap();

        $this->actingAs($this->user)
            ->visit(route('map.bonuses', ['gameMap' => $gameMap]))
            ->see('Map Bonuses')
            ->submitForm('Submit', [
                'xp_bonus'             => .20,
                'skill_training_bonus' => .20,
                'drop_chance_bonus'    => .30,
                'enemy_stat_bonus'     => .34,
            ])->see($gameMap->name . ' now has bonuses.');

        $gameMap = $gameMap->refresh();

        $this->assertEquals(.20, $gameMap->xp_bonus);
    }

    public function testCanSeeMapBonuses() {

        $gameMap = $this->createGameMap([
            'xp_bonus'             => .20,
            'skill_training_bonus' => .20,
            'drop_chance_bonus'    => .30,
            'enemy_stat_bonus'     => .34,
        ]);

        $this->actingAs($this->user)
            ->visit(route('view.map.bonuses', ['gameMap' => $gameMap]))
            ->see('Map Bonuses')
            ->see('XP Bonus:')
            ->see('20%');
    }
}
