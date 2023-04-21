<?php


namespace Tests\Console\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Kingdom;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Setup\Character\CharacterFactory;

class GiveKingdomsToNpcsTest extends TestCase
{
    use RefreshDatabase, CreateNpc;

    private $character;

    public function setUp(): void
    {
        parent::setUp();

        (new CharacterFactory())->createBaseCharacter()
                                ->updateCharacter([
                                    'name' => 'SampleCharacter'
                                ])
                                ->givePlayerLocation()
                                ->kingdomManagement()
                                ->assignKingdom()
                                ->assignBuilding()
                                ->assignKingdom()
                                ->getCharacterFactory()
                                ->banCharacter('Sample Reason');

        $this->createNpc();

    }

    public function testGiveBannedPlayerKingdomsToNPC() {
        DB::table('users')->update([
            'updated_at' => now()->subDays(50)
        ]);

        $this->assertEquals(0, $this->artisan('npc:take-kingdoms'));

        $this->assertNotEmpty(Kingdom::where('npc_owned', true)->get());
    }
}