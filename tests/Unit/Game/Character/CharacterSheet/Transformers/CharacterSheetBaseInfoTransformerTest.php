<?php

namespace Tests\Unit\Game\Character\CharacterSheet\Transformers;

use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Maps\Values\LocationBasedCraftingOptions;
use App\Game\Maps\Values\MapName;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class CharacterSheetBaseInfoTransformerTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_location_based_crafting_options_are_mapped_onto_character_sheet(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::PURGATORY->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($character);

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertSame($locationBasedCraftingOptions->toCharacterSheetArray(), [
            'can_use_work_bench' => $data['can_use_work_bench'],
            'can_access_queen' => $data['can_access_queen'],
            'can_access_labyrinth_oracle' => $data['can_access_labyrinth_oracle'],
            'can_access_seer_camp' => $data['can_access_seer_camp'],
        ]);
    }

    public function test_null_crafting_timestamp_produces_zero_remaining_timeout(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->update(['can_craft_again_at' => null]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($character->refresh());

        $this->assertSame(0, $data['can_craft_again_at']);
    }

    public function test_expired_crafting_timestamp_produces_zero_remaining_timeout(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->update(['can_craft_again_at' => now()->subMinutes(5)]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($character->refresh());

        $this->assertSame(0, $data['can_craft_again_at']);
    }

    public function test_future_crafting_timestamp_produces_the_factual_remaining_duration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->update(['can_craft_again_at' => now()->addSeconds(120)]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($character->refresh());

        $this->assertSame(120, $data['can_craft_again_at']);
    }
}
