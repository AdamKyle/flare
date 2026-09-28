<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGemModifier;
use App\Flare\Models\Gem;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSocket;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Gems\Contracts\CharacterGemEffects;
use App\Game\Gems\Values\CharacterGemModifierType;
use App\Game\Gems\Values\GemAbilityType;
use App\Game\Gems\Values\ResolvedCharacterGemEffects;

class CharacterGemEffectService implements CharacterGemEffects
{
    use FetchEquipped;

    /**
     * Resolve the effects supplied by character-domain Gems socketed into equipped Items.
     *
     * @param int $characterId
     * @return ResolvedCharacterGemEffects
     */
    public function resolveForCharacterId(int $characterId): ResolvedCharacterGemEffects
    {
        $character = Character::find($characterId);

        if (is_null($character)) {
            return new ResolvedCharacterGemEffects;
        }

        $equippedSlots = $this->fetchEquipped($character);

        if (is_null($equippedSlots) || $equippedSlots->isEmpty()) {
            return new ResolvedCharacterGemEffects;
        }

        $equippedSlots->loadMissing('item.sockets.gem.characterModifiers.gameGemAbility');

        $amounts = [];
        $activeAbilities = [];
        $passiveAbilities = [];
        $gemDetails = [];

        foreach ($equippedSlots as $equippedSlot) {
            $item = $equippedSlot->item;

            if (is_null($item)) {
                continue;
            }

            foreach ($item->sockets as $socket) {
                $this->collectSocketEffects($socket, $item, $amounts, $activeAbilities, $passiveAbilities, $gemDetails);
            }
        }

        return new ResolvedCharacterGemEffects(
            $this->capAmounts($amounts),
            $activeAbilities,
            $passiveAbilities,
            $gemDetails,
        );
    }

    /**
     * Collect one equipped socket's character Gem effects.
     *
     * @param ItemSocket $socket
     * @param Item $item
     * @param array $amounts
     * @param array $activeAbilities
     * @param array $passiveAbilities
     * @param array $gemDetails
     * @return void
     */
    private function collectSocketEffects(
        ItemSocket $socket,
        Item $item,
        array &$amounts,
        array &$activeAbilities,
        array &$passiveAbilities,
        array &$gemDetails,
    ): void {
        $gem = $socket->gem;

        if (is_null($gem) || $gem->domain !== Gem::DOMAIN_CHARACTER) {
            return;
        }

        foreach ($gem->characterModifiers as $modifier) {
            if ($modifier->modifier_type === CharacterGemModifierType::GEM_ABILITY) {
                $this->collectAbility($modifier, $gem, $item, $activeAbilities, $passiveAbilities, $gemDetails);

                continue;
            }

            $modifierKey = $modifier->modifier_type->value;
            $amounts[$modifierKey] = ($amounts[$modifierKey] ?? 0.0) + $modifier->amount;
            $gemDetails[] = $this->detail($modifier, $gem, $item);
        }
    }

    /**
     * Collect an equipped Gem Ability as a cache-safe snapshot.
     *
     * @param CharacterGemModifier $modifier
     * @param Gem $gem
     * @param Item $item
     * @param array $activeAbilities
     * @param array $passiveAbilities
     * @param array $gemDetails
     * @return void
     */
    private function collectAbility(
        CharacterGemModifier $modifier,
        Gem $gem,
        Item $item,
        array &$activeAbilities,
        array &$passiveAbilities,
        array &$gemDetails,
    ): void {
        $ability = $modifier->gameGemAbility;

        if (is_null($ability)) {
            return;
        }

        $snapshot = [
            'id' => $ability->id,
            'name' => $ability->name,
            'description' => $ability->description,
            'ability_type' => $ability->ability_type->value,
            'effect_type' => $ability->effect_type->value,
            'attack_types' => $ability->attack_types,
            'proc_chance' => $ability->proc_chance,
            'effect_value' => $ability->effect_value,
            'scaling_source' => $ability->scaling_source?->value,
            'enabled' => $ability->enabled,
            'gem_id' => $gem->id,
            'gem_name' => $gem->name,
            'item_id' => $item->id,
            'item_name' => $item->affix_name,
        ];

        if ($ability->ability_type === GemAbilityType::ACTIVE) {
            $activeAbilities[] = $snapshot;
        } else {
            $passiveAbilities[] = $snapshot;
        }

        $gemDetails[] = $this->detail($modifier, $gem, $item, $ability->name);
    }

    /**
     * Build a factual detail row for one equipped Gem modifier.
     *
     * @param CharacterGemModifier $modifier
     * @param Gem $gem
     * @param Item $item
     * @param string|null $abilityName
     * @return array
     */
    private function detail(CharacterGemModifier $modifier, Gem $gem, Item $item, ?string $abilityName = null): array
    {
        return [
            'gem_id' => $gem->id,
            'gem_name' => $gem->name,
            'tier' => $gem->tier,
            'item_id' => $item->id,
            'item_name' => $item->affix_name,
            'modifier_type' => $modifier->modifier_type->value,
            'amount' => $modifier->amount,
            'ability_name' => $abilityName,
        ];
    }

    /**
     * Apply every aggregate character-Gem cap at the owning resolver boundary.
     *
     * @param array $amounts
     * @return array
     */
    private function capAmounts(array $amounts): array
    {
        foreach ($amounts as $modifierType => $amount) {
            $type = CharacterGemModifierType::from($modifierType);
            $amounts[$modifierType] = min($amount, $this->capFor($type));
        }

        $amounts[CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN->value] = min(
            $amounts[CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN->value] ?? 0.0,
            1.0,
        );

        return $amounts;
    }

    /**
     * Return the aggregate cap for a character Gem modifier type.
     *
     * @param CharacterGemModifierType $modifierType
     * @return float
     */
    private function capFor(CharacterGemModifierType $modifierType): float
    {
        return match ($modifierType) {
            CharacterGemModifierType::BASE_DAMAGE_MOD,
            CharacterGemModifierType::BASE_AC_MOD,
            CharacterGemModifierType::BASE_HEALING_MOD,
            CharacterGemModifierType::BASE_SPELL_DAMAGE_MOD,
            CharacterGemModifierType::FIRE_PENETRATION,
            CharacterGemModifierType::WATER_PENETRATION,
            CharacterGemModifierType::ICE_PENETRATION,
            CharacterGemModifierType::GOLD_GAIN,
            CharacterGemModifierType::GOLD_DUST_GAIN,
            CharacterGemModifierType::SHARDS_GAIN,
            CharacterGemModifierType::COPPER_COIN_GAIN,
            CharacterGemModifierType::CHARACTER_XP_GAIN => 0.50,
            CharacterGemModifierType::CLASS_RANK_XP_GAIN,
            CharacterGemModifierType::CLASS_MASTERY_XP_GAIN,
            CharacterGemModifierType::WEAPON_MASTERY_XP_GAIN,
            CharacterGemModifierType::CLASS_SKILL_XP_GAIN => 1.0,
            CharacterGemModifierType::CLASS_MASTERY_EFFECT,
            CharacterGemModifierType::WEAPON_MASTERY_EFFECT => 0.25,
            CharacterGemModifierType::FIRE_ATONEMENT,
            CharacterGemModifierType::WATER_ATONEMENT,
            CharacterGemModifierType::ICE_ATONEMENT => 0.75,
            CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN => 1.0,
            default => PHP_FLOAT_MAX,
        };
    }
}
