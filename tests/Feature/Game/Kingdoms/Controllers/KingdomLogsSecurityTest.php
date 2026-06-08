<?php

namespace Tests\Feature\Game\Kingdoms\Controllers;

use App\Flare\Models\KingdomLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomLogsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_own_kingdom_log(): void
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

    public function test_character_cannot_delete_another_characters_kingdom_log(): void
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

    public function test_mixed_batch_deletes_only_owned_kingdom_logs(): void
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
