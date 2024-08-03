<?php

namespace Tests\Console\Admin;

use App\Flare\Models\Kingdom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNpc;

class GiveKingdomsToNpcsTest extends TestCase
{
    use CreateNpc, RefreshDatabase;

    private $character;

    public function setUp(): void
    {
        parent::setUp();

        (new CharacterFactory)->createBaseCharacter()
            ->updateCharacter([
                'name' => 'SampleCharacter',
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

    public function testGiveBannedPlayerKingdomsToNPC()
    {
        DB::table('users')->update([
            'updated_at' => now()->subDays(50),
        ]);

        $this->assertEquals(0, $this->artisan('npc:take-kingdoms'));

        $this->assertNotEmpty(Kingdom::where('npc_owned', true)->get());
    }
}
