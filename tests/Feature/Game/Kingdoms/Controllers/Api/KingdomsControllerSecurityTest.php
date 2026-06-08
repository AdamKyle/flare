<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomsControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function testOwnerRenameUpdatesOnlyKingdomName(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'gold_bars' => 10,
            'npc_owned' => false,
            'protected_until' => null,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/'.$kingdom->id.'/rename', [
                'name' => 'Secure Kingdom',
                'character_id' => 999999,
                'current_wood' => 999999,
                'gold_bars' => 999999,
                'npc_owned' => true,
                'protected_until' => now()->addYear(),
            ]);

        $response->assertOk();
        $kingdom = $kingdom->refresh();
        $this->assertSame('Secure Kingdom', $kingdom->name);
        $this->assertSame($character->id, $kingdom->character_id);
        $this->assertSame(1000, $kingdom->current_wood);
        $this->assertSame(10, $kingdom->gold_bars);
        $this->assertFalse($kingdom->npc_owned);
        $this->assertNull($kingdom->protected_until);
    }
}
