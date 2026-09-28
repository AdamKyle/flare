<?php

namespace Tests\Unit\Game\Gems\Builders;

use App\Flare\Models\Gem;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Services\CharacterGemRollService;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGameGemAbility;
use Tests\Traits\CreateGem;

class GemBuilderTest extends TestCase
{
    use CreateCharacterGemModifier, CreateGameGemAbility, CreateGem, RefreshDatabase;

    /**
     * Prove Tier One creates one ability roll and two distinct raw-stat rolls.
     *
     * @return void
     */
    public function test_tier_one_creates_ability_and_two_distinct_raw_stats(): void
    {
        $ability = $this->createGameGemAbility();
        $gem = $this->builder([3, 0, 0, 0, 5, 5])->buildGem(1);
        $gem->load('characterModifiers');

        $this->assertSame('Glinting Bytocchacuaite', $gem->name);
        $this->assertSame(Gem::DOMAIN_CHARACTER, $gem->domain);
        $this->assertCount(3, $gem->characterModifiers);
        $this->assertSame(CharacterGemModifierType::GEM_ABILITY, $gem->characterModifiers[0]->modifier_type);
        $this->assertSame($ability->id, $gem->characterModifiers[0]->game_gem_ability_id);
        $this->assertNotSame($gem->characterModifiers[1]->modifier_type, $gem->characterModifiers[2]->modifier_type);
    }

    /**
     * Prove an exact normalized roll signature reuses the existing character Gem.
     *
     * @return void
     */
    public function test_reuses_exact_matching_character_gem(): void
    {
        $ability = $this->createGameGemAbility();
        $existingGem = $this->createGem(['name' => 'Glinting Bytocchacuaite', 'tier' => 1]);
        $this->createCharacterGemModifier(['gem_id' => $existingGem->id, 'roll_position' => 1, 'modifier_type' => CharacterGemModifierType::GEM_ABILITY, 'amount' => null, 'game_gem_ability_id' => $ability->id]);
        $this->createCharacterGemModifier(['gem_id' => $existingGem->id, 'roll_position' => 2, 'modifier_type' => CharacterGemModifierType::STRENGTH, 'amount' => 5]);
        $this->createCharacterGemModifier(['gem_id' => $existingGem->id, 'roll_position' => 3, 'modifier_type' => CharacterGemModifierType::DEXTERITY, 'amount' => 5]);

        $gem = $this->builder([3, 0, 0, 0, 5, 5])->buildGem(1);

        $this->assertSame($existingGem->id, $gem->id);
        $this->assertSame(1, Gem::where('domain', Gem::DOMAIN_CHARACTER)->count());
    }

    /**
     * Prove disabled definitions are excluded from newly rolled Tier One Gems.
     *
     * @return void
     */
    public function test_disabled_ability_is_never_newly_rolled(): void
    {
        $this->createGameGemAbility(['enabled' => false]);
        $enabledAbility = $this->createGameGemAbility(['name' => 'Enabled Ability', 'enabled' => true]);

        $gem = $this->builder([3, 0, 0, 0, 5, 5])->buildGem(1);

        $this->assertSame($enabledAbility->id, $gem->characterModifiers()->first()->game_gem_ability_id);
    }

    /**
     * Build a GemBuilder with a deterministic number sequence.
     *
     * @param array $numbers
     * @return GemBuilder
     */
    private function builder(array $numbers): GemBuilder
    {
        $random = $this->createStub(RandomNumberGenerator::class);
        $random->method('numberBetween')->willReturnOnConsecutiveCalls(...$numbers);
        $chance = $this->createStub(ChanceCalculator::class);

        return new GemBuilder($random, new CharacterGemRollService($random, $chance));
    }
}
