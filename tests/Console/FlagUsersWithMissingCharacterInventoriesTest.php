<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class FlagUsersWithMissingCharacterInventoriesTest extends TestCase
{
    use RefreshDatabase;

    public function testDryRunDoesNotFlagUserWithMissingInventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->inventory()->delete();

        $this->assertSame(
            0,
            $this->artisan('flag:users-with-missing-character-inventories', ['--dry-run' => true]),
        );

        $this->assertFalse($character->user->refresh()->will_be_deleted);
        $this->assertTrue(Character::whereKey($character->id)->exists());
    }

    public function testCommandOnlyFlagsUserWithMissingInventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->inventory()->delete();

        $this->assertSame(0, $this->artisan('flag:users-with-missing-character-inventories'));

        $this->assertTrue($character->user->refresh()->will_be_deleted);
        $this->assertTrue(Character::whereKey($character->id)->exists());
        $this->assertFalse(Inventory::where('character_id', $character->id)->exists());
    }
}
