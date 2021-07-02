<?php

namespace Tests\Unit\Admin\Console;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GiveKingdomsToNpcsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void {
        parent::setup();

        (new CharacterFactory)->createBaseCharacter()
                              ->givePlayerLocation()
                              ->kingdomManagement()
                              ->assignKingdom()
                              ->assignBuilding([
                                  'name'     => 'Walls',
                                  'is_walls' => true
                              ])
                              ->assignBuilding([
                                  'name'    => 'Farm',
                                  'is_farm' => true
                              ])
                              ->assignBuilding()
                              ->assignUnits();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testGiveKingdomsToNpc() {
        User::first()->update([
            'is_banned'=> true,
        ]);

        DB::table('users')->update(['updated_at' => now()->subDays(25)]);

        $this->assertEquals(0, $this->artisan('npc:take-kingdoms'));

        $this->assertTrue(Kingdom::where('npc_owned', true)->get()->isNotEmpty());
    }
}
