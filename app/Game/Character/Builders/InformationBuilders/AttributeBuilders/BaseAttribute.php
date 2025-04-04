<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\Item;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\Builders\StatDetailsBuilder\Concerns\BasicItemDetails;
use App\Game\Character\CharacterInventory\Values\ItemType;
use Illuminate\Support\Collection;

class BaseAttribute
{
    use BasicItemDetails;

    protected Character $character;

    protected ?Collection $inventory;

    protected Collection $skills;

    public function initialize(Character $character, Collection $skills, ?Collection $inventory): void
    {

        $this->character = $character;
        $this->inventory = $inventory;
        $this->skills = $skills;
    }

    /**
     * Get attribute bonus from all item affixes.
     */
    protected function getAttributeBonusFromAllItemAffixes(string $attribute): float
    {
        return $this->inventory->sum('item.itemPrefix.'.$attribute.'_mod') +
            $this->inventory->sum('item.itemSuffix.'.$attribute.'_mod');
    }

    protected function getAttributeBonusFromAllItemAffixesDetails(string $attribute, bool $voided = false, string|array|null $onlyForType = null): array
    {
        $details = [];

        if (is_null($this->inventory)) {
            return $details;
        }

        foreach ($this->inventory as $slot) {

            if (! is_null($onlyForType)) {

                if (! is_array($onlyForType)) {

                    if ($onlyForType === WeaponTypes::RING && $slot->item->type !== $onlyForType) {
                        continue;
                    }

                    if ($slot->item->type !== $onlyForType) {

                        if (! $this->hasAffixesAffectingStat($slot->item, $attribute)) {
                            continue;
                        }
                    }
                } else {
                    if (! in_array($slot->item->type, $onlyForType)) {
                        continue;
                    }
                }

            }

            $details[] = [
                'item_details' => $this->getBasicDetailsOfItem($slot->item),
                $attribute => number_format($slot->item->{$attribute}),
                'affixes' => $voided ? [] : $this->fetchAffixes($slot->item, $attribute),
            ];
        }

        return $details;
    }

    private function hasAffixesAffectingStat(Item $item, string $attribute): bool
    {
        if (! is_null($item->item_prefix_id)) {
            if ($item->itemPrefix->{$attribute.'_mod'} > 0) {
                return true;
            }
        }

        if (! is_null($item->item_suffix_id)) {
            if ($item->itemSuffix->{$attribute.'_mod'} > 0) {
                return true;
            }
        }

        return false;
    }

    private function fetchAffixes(Item $item, string $attribute): array
    {
        $details = [];

        if (! is_null($item->item_prefix_id)) {
            if ($item->itemPrefix->{$attribute.'_mod'} > 0) {
                $details[] = [
                    'name' => $item->itemPrefix->name,
                    'amount' => $item->itemPrefix->{$attribute.'_mod'},
                ];
            }
        }

        if (! is_null($item->item_suffix_id)) {
            if ($item->itemSuffix->{$attribute.'_mod'} > 0) {
                $details[] = [
                    'name' => $item->itemSuffix->name,
                    'amount' => $item->itemSuffix->{$attribute.'_mod'},
                ];
            }
        }

        return $details;
    }

    /**
     * Fetch attribute bonus from skills.
     */
    public function fetchBaseAttributeFromSkills(string $baseAttribute): float
    {
        $totalPercent = 0;

        foreach ($this->skills as $skill) {
            $totalPercent += ($skill->baseSkill->{$baseAttribute.'_mod_bonus_per_level'} * $skill->level);
        }

        return $totalPercent;
    }

    public function fetchBaseAttributeFromSkillsDetails(string $baseAttribute): array
    {
        $details = [];

        foreach ($this->skills as $skill) {

            if ($this->character->game_class_id === $skill->baseSkill->game_class_id) {
                $amount = $skill->baseSkill->{$baseAttribute.'_mod_bonus_per_level'} * $skill->level;

                if ($amount > 0) {
                    $details[] = [
                        'name' => $skill->baseSkill->name,
                        'amount' => $amount,
                    ];
                }
            }
        }

        return $details;
    }

    /**
     * Should we include skill damage?
     */
    protected function shouldIncludeSkillDamage(GameClass $class, string $type): bool
    {
        switch ($type) {
            case 'weapon':
                return $class->type()->isNonCaster();
            case 'spell':
                return $class->type()->isCaster();
            case 'healing':
                return $class->type()->isHealer();
            default:
                false;
        }

        return false;
    }

    /**
     * Get damage from items.
     */
    protected function getDamageFromWeapons(string $position): int
    {

        $itemTypes = array_map(fn($case) => $case->value, ItemType::cases());

        if ($position === 'both') {
            return $this->inventory->whereIn('item.type', $itemTypes)->sum('item.base_damage');
        }

        return $this->inventory->whereIn('item.type', $itemTypes)->where('position', $position)
            ->sum('item.base_damage');
    }

    protected function getDamageFromItems(string $type, string $position): int
    {

        if (is_null($this->inventory)) {
            return 0;
        }

        if ($position === 'both') {
            return $this->inventory->whereIn('item.type', $type)->sum('item.base_damage');
        }

        return $this->inventory->where('item.type', $type)
            ->where('position', $position)
            ->sum('item.base_damage');
    }

    /**
     * Get healing from items.
     */
    protected function getHealingFromItems(string $type, string $position): int
    {

        if ($position === 'both') {
            return $this->inventory->where('item.type', $type)->sum('item.base_healing');
        }

        return $this->inventory->where('item.type', $type)->where('position', $position)->sum('item.base_healing');
    }
}
