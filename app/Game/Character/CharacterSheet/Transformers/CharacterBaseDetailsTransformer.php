<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Transformers\BaseTransformer;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Flare\Values\ClassAttackValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use League\Fractal\Resource\Item;

class CharacterBaseDetailsTransformer extends BaseTransformer
{
    protected array $defaultIncludes = [
        'inventory_count',
    ];

    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {

        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character);
        $gameClass = GameClass::find($character->game_class_id);

        return [
            'game_map_id' => $character->map->game_map_id,
            'class' => $gameClass->name,
            'class_id' => $gameClass->id,
            'race' => $character->race->name,
            'race_id' => $character->race->id,
            'to_hit_stat' => $character->class->to_hit_stat,
            'level' => $character->level,
            'max_level' => $this->getMaxLevel($character),
            'xp' => (int) $character->xp,
            'xp_next' => (int) $character->xp_next,
            'str_modded' => $characterStatBuilder->statMod('str'),
            'dur_modded' => $characterStatBuilder->statMod('dur'),
            'dex_modded' => $characterStatBuilder->statMod('dex'),
            'chr_modded' => $characterStatBuilder->statMod('chr'),
            'int_modded' => $characterStatBuilder->statMod('int'),
            'agi_modded' => $characterStatBuilder->statMod('agi'),
            'focus_modded' => $characterStatBuilder->statMod('focus'),
            'attack' => $characterStatBuilder->buildTotalAttack(),
            'health' => $characterStatBuilder->buildHealth(),
            'ac' => $characterStatBuilder->buildDefence(),
            'class_bonus_chance' => (new ClassAttackValue($character))->buildAttackData()['chance'],
            'gold' => $character->gold,
            'gold_dust' => $character->gold_dust,
            'shards' => $character->shards,
            'copper_coins' => $character->copper_coins,
            'can_access_hell_forged' => $character->map->gameMap->mapType()->isHell(),
            'can_access_purgatory_chains' => $character->map->gameMap->mapType()->isPurgatory(),
            'can_access_labyrinth_oracle' => $character->map->gameMap->mapType()->isLabyrinth(),
            'can_access_twisted_earth' => $character->map->gameMap->mapType()->isTwistedMemories(),
            'is_in_timeout' => ! is_null($character->user->timeout_until),
            'weapon_attack' => $characterStatBuilder->buildDamage(ItemType::validWeapons()),
            'voided_weapon_attack' => $characterStatBuilder->buildDamage(ItemType::validWeapons(), true),
            'ring_damage' => $characterStatBuilder->buildDamage(ItemType::RING->value),
            'spell_damage' => $characterStatBuilder->buildDamage(ItemType::SPELL_DAMAGE->value),
            'voided_spell_damage' => $characterStatBuilder->buildDamage(ItemType::SPELL_DAMAGE->value, true),
            'healing_amount' => $characterStatBuilder->buildHealing(),
            'voided_healing_amount' => $characterStatBuilder->buildHealing(true),
            'gold_bars' => $this->fetchGoldBarsAmount($character),
            'map_name' => $character->map->gameMap->name,
        ];
    }

    /**
     * Includes the inventory count.
     *
     * @return Item
     */
    public function includeInventoryCount(Character $character)
    {
        return $this->item($character, new CharacterInventoryCountTransformer);
    }

    /**
     * Fetches the gold bar amount.
     */
    private function fetchGoldBarsAmount(Character $character): int
    {
        return $character->kingdoms->sum('gold_bars');
    }
}
