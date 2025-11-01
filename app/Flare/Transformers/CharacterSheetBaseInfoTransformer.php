<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Values\ClassAttackValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Exception;

class CharacterSheetBaseInfoTransformer extends BaseTransformer
{
    private bool $ignoreReductions = false;

    protected array $defaultIncludes = [
        'inventory_count',
        'resistance_info',
        'reincarnation_info',
    ];

    public function setIgnoreReductions(bool $ignoreReductions): void
    {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * Gets the response data for the character sheet
     *
     * @throws Exception
     */
    public function transform(Character $character): array
    {
        $characterStatBuilder = resolve(CharacterStatBuilder::class)->setCharacter($character, $this->ignoreReductions);
        $gameClass = GameClass::find($character->game_class_id);

        return [
            'id' => $character->id,
            'user_id' => $character->user_id,
            'game_map_id' => $character->map->game_map_id,
            'name' => $character->name,
            'class' => $gameClass->name,
            'class_id' => $gameClass->id,
            'race' => $character->race->name,
            'race_id' => $character->race->id,
            'to_hit_stat' => $character->class->to_hit_stat,
            'level' => $character->level,
            'max_level' => $this->getMaxLevel($character),
            'xp' => (int) $character->xp,
            'xp_next' => (int) $character->xp_next,
            'str_raw' => $character->str,
            'dur_raw' => $character->dur,
            'dex_raw' => $character->dex,
            'chr_raw' => $character->chr,
            'int_raw' => $character->int,
            'agi_raw' => $character->agi,
            'focus_raw' => $character->focus,
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
            'resurrection_chance' => $characterStatBuilder->buildResurrectionChance(),
            'spell_evasion' => $characterStatBuilder,
            'elemental_atonements' => $characterStatBuilder->buildElementalAtonement(),
            'weapon_attack' => $characterStatBuilder->buildDamage('weapon'),
            'voided_weapon_attack' => $characterStatBuilder->buildDamage('weapon', true),
            'ring_damage' => $characterStatBuilder->buildDamage('ring'),
            'spell_damage' => $characterStatBuilder->buildDamage('spell-damage'),
            'voided_spell_damage' => $characterStatBuilder->buildDamage('spell-damage', true),
            'healing_amount' => $characterStatBuilder->buildHealing(),
            'voided_healing_amount' => $characterStatBuilder->buildHealing(true),
            'gold_bars' => $this->fetchGoldBarsAmount($character),
            'map_name' => $character->map->gameMap->name,
        ];
    }

    public function includeInventoryCount(Character $character)
    {
        return $this->item($character, new CharacterInventoryCountTransformer);
    }

    public function includeResistanceInfo(Character $character)
    {
        return $this->item($character, new CharacterResistanceInfoTransformer);
    }

    public function includereincarnationInfo(Character $character)
    {
        return $this->item($character, new CharacterReincarnationInfoTransformer);
    }

    private function fetchGoldBarsAmount(Character $character): int
    {
        return $character->kingdoms->sum('gold_bars');
    }
}
