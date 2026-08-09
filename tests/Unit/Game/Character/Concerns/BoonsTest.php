<?php

namespace Tests\Unit\Game\Character\Concerns;

use App\Game\Character\Concerns\Boons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateItem;

class BoonsTest extends TestCase
{
    use CreateCharacterBoon, CreateItem, RefreshDatabase;

    public function test_fetch_character_boons_returns_only_active_boons_with_their_item(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $activeItem = $this->createItem();
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'last_for_minutes' => 60,
            'started' => now(),
            'amount_used' => 1,
            'item_id' => $activeItem->id,
            'complete' => now()->addHour(),
        ]);

        $expiredItem = $this->createItem();
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'last_for_minutes' => 60,
            'started' => now(),
            'amount_used' => 1,
            'item_id' => $expiredItem->id,
            'complete' => now()->subHour(),
        ]);

        $boons = (new class
        {
            use Boons;
        })->fetchCharacterBoons($character);

        $this->assertCount(1, $boons);
        $this->assertSame($activeItem->id, $boons->first()->itemUsed->id);
    }

    public function test_gains_additional_level_on_level_up_is_true_when_active_boon_item_grants_it(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem(['gains_additional_level' => true]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'last_for_minutes' => 60,
            'started' => now(),
            'amount_used' => 1,
            'item_id' => $item->id,
            'complete' => now()->addHour(),
        ]);

        $gainsAdditionalLevel = (new class
        {
            use Boons;
        })->gainsAdditionalLevelOnLevelUp($character);

        $this->assertTrue($gainsAdditionalLevel);
    }

    public function test_gains_additional_level_on_level_up_is_false_without_a_qualifying_boon(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $gainsAdditionalLevel = (new class
        {
            use Boons;
        })->gainsAdditionalLevelOnLevelUp($character);

        $this->assertFalse($gainsAdditionalLevel);
    }

    public function test_additional_levels_to_gain_sums_amount_used_and_adds_one(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem(['gains_additional_level' => true]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'last_for_minutes' => 60,
            'started' => now(),
            'item_id' => $item->id,
            'amount_used' => 2,
            'complete' => now()->addHour(),
        ]);

        $additionalLevels = (new class
        {
            use Boons;
        })->additionalLevelsToGain($character);

        $this->assertSame(3, $additionalLevels);
    }

    public function test_fetch_xp_bonus_multiplies_by_amount_used_when_item_can_stack(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem(['xp_bonus' => 2.5, 'can_stack' => true]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'last_for_minutes' => 60,
            'started' => now(),
            'item_id' => $item->id,
            'amount_used' => 3,
            'complete' => now()->addHour(),
        ]);

        $xpBonus = (new class
        {
            use Boons;
        })->fetchXpBonus($character);

        $this->assertSame(7.5, $xpBonus);
    }

    public function test_fetch_fight_time_out_modifier_ignores_amount_used_when_item_cannot_stack(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem(['fight_time_out_mod_bonus' => 0.5, 'can_stack' => false]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'last_for_minutes' => 60,
            'started' => now(),
            'item_id' => $item->id,
            'amount_used' => 5,
            'complete' => now()->addHour(),
        ]);

        $modifier = (new class
        {
            use Boons;
        })->fetchFightTimeOutModifier($character);

        $this->assertSame(0.5, $modifier);
    }

    public function test_fetch_move_time_out_modifier_returns_zero_without_a_boon(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $modifier = (new class
        {
            use Boons;
        })->fetchMoveTimOutModifier($character);

        $this->assertSame(0.0, $modifier);
    }
}
