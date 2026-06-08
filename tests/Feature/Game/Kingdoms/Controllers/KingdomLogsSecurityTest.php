<?php

namespace Tests\Feature\Game\Kingdoms\Controllers;

use App\Flare\Models\KingdomLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomLogsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function testOwnerCanDeleteOwnKingdomLog(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $log = KingdomLog::factory()->create([
            'character_id' => $character->id,
            'status' => 1,
            'opened' => false,
            'published' => true,
        ]);

        $this->actingAs($character->user);
        $this->call('POST', route('game.kingdom.delete-log', [
                'character' => $character->id,
                'kingdomLog' => $log->id,
            ]));

        $this->assertNull(KingdomLog::find($log->id));
    }

    public function testCharacterCannotDeleteAnotherCharactersKingdomLog(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $log = KingdomLog::factory()->create([
            'character_id' => $otherCharacter->id,
            'status' => 1,
            'opened' => false,
            'published' => true,
        ]);

        $this->actingAs($character->user);
        $this->call('POST', route('game.kingdom.delete-log', [
                'character' => $character->id,
                'kingdomLog' => $log->id,
            ]));

        $this->assertNotNull(KingdomLog::find($log->id));
    }

    public function testMixedBatchDeletesOnlyOwnedKingdomLogs(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $ownedLog = KingdomLog::factory()->create([
            'character_id' => $character->id,
            'status' => 1,
            'opened' => false,
            'published' => true,
        ]);
        $otherLog = KingdomLog::factory()->create([
            'character_id' => $otherCharacter->id,
            'status' => 1,
            'opened' => false,
            'published' => true,
        ]);

        $this->actingAs($character->user);
        $this->call('POST', route('game.kingdom.batch-delete-logs', [
                'character' => $character->id,
            ]), [
                'logs' => [$ownedLog->id, $otherLog->id],
            ]);

        $this->assertNull(KingdomLog::find($ownedLog->id));
        $this->assertNotNull(KingdomLog::find($otherLog->id));
    }
}
