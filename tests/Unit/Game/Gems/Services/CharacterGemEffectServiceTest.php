<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Game\Gems\Services\CharacterGemEffectService;
use App\Game\Gems\Services\GemAbilityDuplicateService;
use App\Game\Gems\Values\CharacterGemModifierType;
use App\Game\Gems\Values\GemAbilityEffectType;
use App\Game\Gems\Values\ResolvedCharacterGemEffects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGameGemAbility;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class CharacterGemEffectServiceTest extends TestCase
{
    use CreateCharacterGemModifier, CreateGameGemAbility, CreateGem, CreateItem, RefreshDatabase;

    public function test_it_resolves_only_equipped_character_gems_and_applies_caps(): void
    {
        $equippedGem = $this->createGem(['name' => 'Equipped Gem', 'tier' => 4]);
        $this->createCharacterGemModifier([
            'gem_id' => $equippedGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::FIRE_ATONEMENT,
            'amount' => 0.80,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $equippedGem->id,
            'roll_position' => 2,
            'modifier_type' => CharacterGemModifierType::FIRE_PENETRATION,
            'amount' => 0.60,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $equippedGem->id,
            'roll_position' => 3,
            'modifier_type' => CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN,
            'amount' => 1,
        ]);
        $equippedItem = $this->createItem(['type' => 'body', 'socket_count' => 1]);
        $equippedItem->sockets()->create(['gem_id' => $equippedGem->id]);

        $unequippedGem = $this->createGem(['name' => 'Bag Gem', 'tier' => 1]);
        $this->createCharacterGemModifier([
            'gem_id' => $unequippedGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::STRENGTH,
            'amount' => 25,
        ]);
        $unequippedItem = $this->createItem(['type' => 'helmet', 'socket_count' => 1]);
        $unequippedItem->sockets()->create(['gem_id' => $unequippedGem->id]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($equippedItem, true, 'body')
            ->giveItem($unequippedItem)
            ->getCharacter();

        $effects = (new CharacterGemEffectService(new GemAbilityDuplicateService))->resolveForCharacterId($character->id);

        $this->assertSame(0.75, $effects->fireAtonement());
        $this->assertSame(0.50, $effects->firePenetration());
        $this->assertTrue($effects->gainsAdditionalLevel());
        $this->assertSame(0.0, $effects->rawStat('strength'));
        $this->assertSame('Equipped Gem', $effects->detailsFor(CharacterGemModifierType::FIRE_ATONEMENT)[0]['gem_name']);
    }

    public function test_passive_bonus_is_action_specific_and_capped(): void
    {
        $effects = new ResolvedCharacterGemEffects(passiveAbilities: [
            ['effect_type' => 'weapon_damage_mod', 'effect_value' => 0.35, 'attack_types' => ['attack']],
            ['effect_type' => 'weapon_damage_mod', 'effect_value' => 0.30, 'attack_types' => ['attack']],
            ['effect_type' => 'weapon_damage_mod', 'effect_value' => 0.40, 'attack_types' => ['attack_and_cast']],
        ]);

        $this->assertSame(0.50, $effects->passiveBonusFor(GemAbilityEffectType::WEAPON_DAMAGE_MOD, 'attack'));
        $this->assertSame(0.40, $effects->passiveBonusFor(GemAbilityEffectType::WEAPON_DAMAGE_MOD, 'attack_and_cast'));
        $this->assertSame(0.0, $effects->passiveBonusFor(GemAbilityEffectType::WEAPON_DAMAGE_MOD, 'cast'));
    }

    public function test_duplicate_active_ability_on_one_item_has_one_proc_opportunity_and_keeps_all_stats(): void
    {
        $ability = $this->createGameGemAbility(['name' => 'Flames of the Heretic']);
        $firstGem = $this->createGem(['name' => 'First Gem']);
        $secondGem = $this->createGem(['name' => 'Second Gem']);
        $this->createCharacterGemModifier([
            'gem_id' => $firstGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $firstGem->id,
            'roll_position' => 2,
            'modifier_type' => CharacterGemModifierType::DURABILITY,
            'amount' => 9,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $secondGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $ability->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $secondGem->id,
            'roll_position' => 2,
            'modifier_type' => CharacterGemModifierType::STRENGTH,
            'amount' => 7,
        ]);
        $item = $this->createItem(['type' => 'body', 'socket_count' => 2]);
        $item->sockets()->create(['gem_id' => $firstGem->id]);
        $item->sockets()->create(['gem_id' => $secondGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $effects = (new CharacterGemEffectService(new GemAbilityDuplicateService))->resolveForCharacterId($character->id);

        $this->assertCount(1, $effects->activeAbilitySnapshots());
        $this->assertSame($ability->id, $effects->activeAbilitySnapshots()[0]['id']);
        $this->assertSame(9.0, $effects->rawStat('dur'));
        $this->assertSame(7.0, $effects->rawStat('str'));
    }

    public function test_duplicate_active_ability_across_equipped_items_has_one_proc_opportunity(): void
    {
        $ability = $this->createGameGemAbility();
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();

        foreach ([$firstGem, $secondGem] as $gem) {
            $this->createCharacterGemModifier([
                'gem_id' => $gem->id,
                'roll_position' => 1,
                'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
                'amount' => null,
                'game_gem_ability_id' => $ability->id,
            ]);
        }

        $body = $this->createItem(['type' => 'body', 'socket_count' => 1]);
        $helmet = $this->createItem(['type' => 'helmet', 'socket_count' => 1]);
        $body->sockets()->create(['gem_id' => $firstGem->id]);
        $helmet->sockets()->create(['gem_id' => $secondGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($body, true, 'body')
            ->giveItem($helmet, true, 'helmet')
            ->getCharacter();

        $effects = (new CharacterGemEffectService(new GemAbilityDuplicateService))->resolveForCharacterId($character->id);

        $this->assertCount(1, $effects->activeAbilitySnapshots());
    }

    public function test_different_active_abilities_both_remain_eligible(): void
    {
        $firstAbility = $this->createGameGemAbility();
        $secondAbility = $this->createGameGemAbility();
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();
        $this->createCharacterGemModifier([
            'gem_id' => $firstGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $firstAbility->id,
        ]);
        $this->createCharacterGemModifier([
            'gem_id' => $secondGem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::GEM_ABILITY,
            'amount' => null,
            'game_gem_ability_id' => $secondAbility->id,
        ]);
        $item = $this->createItem(['type' => 'body', 'socket_count' => 2]);
        $item->sockets()->create(['gem_id' => $firstGem->id]);
        $item->sockets()->create(['gem_id' => $secondGem->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $effects = (new CharacterGemEffectService(new GemAbilityDuplicateService))->resolveForCharacterId($character->id);

        $this->assertCount(2, $effects->activeAbilitySnapshots());
    }
}
