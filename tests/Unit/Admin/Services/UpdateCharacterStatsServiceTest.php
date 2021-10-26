<?php

namespace Tests\Unit\Admin\Services;

use DB;
use Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Mail\GenericMail;
use App\Admin\Services\UpdateCharacterStatsService;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateUser;
use Tests\TestCase;

class UpdateCharacterStatsServiceTest extends TestCase
{
    use RefreshDatabase, CreateUser;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setup();

        $this->baseSetUp();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testUpdateRacialStats() {
        $race = GameRace::find($this->character->game_race_id);

        $oldRace = $race->replicate();

        $race->update([
            'str_mod' => 100
        ]);

        resolve(UpdateCharacterStatsService::class)->updateRacialStats($oldRace, $race->refresh());

        $this->assertEquals(101, $this->character->refresh()->str);
    }

    public function testUpdateClassStats() {
        $class = GameClass::find($this->character->game_class_id);

        $oldClass = $class->replicate();

        $class->update([
            'str_mod' => 100
        ]);

        resolve(UpdateCharacterStatsService::class)->updateClassStats($oldClass, $class->refresh());

        $this->assertEquals(101, $this->character->refresh()->str);
    }

    public function testUpdateClassStatsAndCharacterIsAboveLevelOne() {
        $class = GameClass::find($this->character->game_class_id);

        $oldClass = $class->replicate();

        $this->character->update([
            'level' => 2
        ]);

        $class->update([
            'str_mod' => 100,
            'damage_stat' => 'dex',
        ]);

        resolve(UpdateCharacterStatsService::class)->updateClassStats($oldClass, $class->refresh());

        $this->assertEquals(100, $this->character->refresh()->str);
        $this->assertEquals(2, $this->character->refresh()->dex);
    }

    public function testUpdateClassStatsChangeDamageStatNotOnline() {
        $class = GameClass::find($this->character->game_class_id);

        $oldClass = $class->replicate();

        $class->update([
            'str_mod' => 100,
            'damage_stat' => 'dex'
        ]);

        Mail::fake();

        resolve(UpdateCharacterStatsService::class)->updateClassStats($oldClass, $class->refresh());

        $this->assertEquals(101, $this->character->refresh()->str);
        $this->assertEquals(1, $this->character->refresh()->dex);
    }

    public function testUpdateClassStatsChangeDamageStatIsOnline() {
        $class = GameClass::find($this->character->game_class_id);

        $oldClass = $class->replicate();

        $class->update([
            'str_mod' => 100,
            'damage_stat' => 'dex'
        ]);

        $this->actingAs($this->character->user);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $this->character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        Mail::fake();

        resolve(UpdateCharacterStatsService::class)->updateClassStats($oldClass, $class->refresh());

        $this->assertEquals(101, $this->character->refresh()->str);
        $this->assertEquals(1, $this->character->refresh()->dex);

        Mail::assertNotSent(GenericMail::class);
    }


    protected function baseSetUp() {

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->getCharacter(false);
    }
}
