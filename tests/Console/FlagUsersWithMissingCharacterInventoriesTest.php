<?php

namespace Tests\Console;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class FlagUsersWithMissingCharacterInventoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_flag_user_with_missing_inventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->inventory()->delete();

        $this->artisan('flag:users-with-missing-character-inventories', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertFalse($character->user->refresh()->will_be_deleted);
        $this->assertTrue(Character::whereKey($character->id)->exists());
    }

    public function test_command_only_flags_user_with_missing_inventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->inventory()->delete();

        $this->artisan('flag:users-with-missing-character-inventories')->assertExitCode(0);

        $this->assertTrue($character->user->refresh()->will_be_deleted);
        $this->assertTrue(Character::whereKey($character->id)->exists());
        $this->assertFalse(Inventory::where('character_id', $character->id)->exists());
    }
}
