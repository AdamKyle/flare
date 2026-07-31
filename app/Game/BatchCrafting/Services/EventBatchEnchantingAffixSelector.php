<?php

namespace App\Game\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Skills\Values\SkillTypeValue;

class EventBatchEnchantingAffixSelector
{
    public function affixIdsForEventEnchant(Character $character, Item $item): array
    {
        $enchantingSkill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->first();

        if (is_null($enchantingSkill)) {
            return [];
        }

        $affixes = ItemAffix::where('skill_level_required', '<=', $enchantingSkill->level)
            ->where('cost', '<=', $character->gold)
            ->orderBy('cost')
            ->get();

        $prefix = $affixes->first(fn (ItemAffix $affix): bool => $this->isPrefix($affix));
        $suffix = $affixes->first(fn (ItemAffix $affix): bool => ! $this->isPrefix($affix));

        if (! is_null($prefix) && ! is_null($suffix)) {
            return [$prefix->id, $suffix->id];
        }

        $fallback = $prefix ?? $suffix;

        if (is_null($fallback)) {
            return [];
        }

        return [$fallback->id];
    }

    private function isPrefix(ItemAffix $affix): bool
    {
        return $affix->type === 'prefix';
    }
}
