<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\GameClass;
use App\Flare\Models\Survey;
use App\Flare\Values\AutomationType;
use App\Flare\Values\ClassAttackValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Exception;

class CharacterSheetBaseInfoTransformer extends BaseTransformer
{
    private bool $ignoreReductions = false;

    protected array $defaultIncludes = [
        'inventory_count',
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
            'name' => $character->name,
            'class' => $gameClass->name,
            'class_id' => $gameClass->id,
            'race' => $character->race->name,
            'race_id' => $character->race->id,
            'to_hit_stat' => $character->class->to_hit_stat,
            'level' => number_format($character->level),
            'max_level' => number_format($this->getMaxLevel($character)),
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
            'class_bonus_chance' => (new ClassAttackValue($character))->buildAttackData(),
            'gold' => number_format($character->gold),
            'gold_dust' => number_format($character->gold_dust),
            'shards' => number_format($character->shards),
            'copper_coins' => number_format($character->copper_coins),
            'resurrection_chance' => $characterStatBuilder->buildResurrectionChance(),
        ];
    }

    public function includeInventoryCount(Character $character)
    {
        return $this->item($character, new CharacterInventoryCountTransformer);
    }
}
