<?php

namespace App\Game\Character\Builders\StatDetailsBuilder;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Builders\StatDetailsBuilder\Concerns\BasicItemDetails;
use App\Game\Character\Concerns\FetchEquipped;
use Facades\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ItemSkillAttribute;
use Illuminate\Support\Collection;

class StatModifierDetails
{
    use BasicItemDetails, FetchEquipped;

    private ?Collection $equipped = null;

    private ?Character $character = null;

    public function __construct(private readonly CharacterStatBuilder $characterStatBuilder) {}

    /**
     * Set the character.
     *
     * @return $this
     */
    public function setCharacter(Character $character): StatModifierDetails
    {

        $this->character = $character;

        $this->equipped = $this->fetchEquipped($character);

        return $this;
    }

    /**
     * Get stat details for a specific stat.
     */
    public function forStat(string $stat): array
    {

        $details = [];

        $characterStatBuilder = $this->characterStatBuilder->setCharacter($this->character);

        $details['base_value'] = $this->character->{$stat};
        $details['modded_value'] = $characterStatBuilder->statMod($stat);
        $details['items_equipped'] = array_values($this->fetchItemDetails($stat));
        $details['boon_details'] = $this->fetchBoonDetails($stat);
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesDetails($stat);
        $details['ancestral_item_skill_data'] = $this->fetchAncestralItemSkills($stat);
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();

        return $details;
    }

    public function buildSpecificBreakDown(string $type, bool $isVodied = false): array
    {
        switch ($type) {
            case 'health':
                return $this->fetchHealthBreakDown($isVodied);
            case 'ac':
                return $this->buildDefenceBreakDown($isVodied);
            case 'weapon_damage':
                return $this->buildDamageBreakDown(ItemType::validWeapons(), $isVodied);
            case 'spell_damage':
                return $this->buildDamageBreakDown([ItemType::SPELL_DAMAGE->value], $isVodied);
            case 'ring_damage':
                return $this->buildDamageBreakDown([ItemType::RING->value], $isVodied);
            case 'heal_for':
                return $this->buildDamageBreakDown([ItemType::SPELL_HEALING->value], $isVodied);
            default:
                return [];
        }
    }

    /**
     * Fetch Health Break Down.
     */
    public function fetchHealthBreakDown(bool $isVoided): array
    {
        $details = [];

        $details['stat_amount'] = $this->character->getInformation()->statMod('dur', $isVoided);
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesForHealth();
        $details['items_equipped'] = array_values($this->fetchItemDetails('dur'));
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();

        return $details;
    }

    /**
     * Build Defence Details.
     */
    public function buildDefenceBreakDown(bool $isVoided): array
    {

        $details = [];

        $details['class_bonus_details'] = $this->fetchClassBonusesEffecting('base_ac');
        $details['boon_details'] = $this->fetchBoonDetails('base_ac');
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesDetails('base_ac');
        $details['ancestral_item_skill_data'] = $this->fetchAncestralItemSkills('base_ac');
        $details['items_equipped'] = array_values($this->fetchItemDetails('base_ac'));
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();

        return array_merge($details, $this->character->getInformation()->getDefenceBuilder()->buildDefenceBreakDownDetails($isVoided));
    }

    public function buildDamageBreakDown(string|array $types, bool $isVoided): array
    {
        $types = $this->normalizeDamageTypes($types);
        $details = [];

        $damageStatAmount = $this->character->getInformation()->statMod($this->character->damage_stat, $isVoided);

        $details['damage_stat_name'] = $this->character->damage_stat;
        $details['damage_stat_amount'] = $damageStatAmount;
        $details['non_equipped_damage_amount'] = 0;
        $details['non_equipped_percentage_of_stat_used'] = 0;
        $details['spell_damage_stat_amount_to_use'] = 0;
        $details['percentage_of_stat_used'] = 0;
        $details['total_damage_for_type'] = $this->character->getInformation()->buildDamage($types, $isVoided);
        $details['base_damage'] = 0;
        $details['items_equipped'] = $this->fetchDamageOrHealingEquipmentBreakDown($types);
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();

        $details = $this->setNonEquippedDamageDetails($details, $types, $damageStatAmount);

        if (in_array(ItemType::SPELL_DAMAGE->value, $types) && $this->character->classType()->isHeretic()) {
            $value = $damageStatAmount * 0.15;

            $details['spell_damage_stat_amount_to_use'] = number_format(max($value, 5));
            $details['percentage_of_stat_used'] = 0.15;
        }

        $classSpecialtyStat = match (true) {
            in_array(ItemType::SPELL_DAMAGE->value, $types) => 'base_spell_damage',
            in_array(ItemType::SPELL_HEALING->value, $types) => 'base_healing',
            default => 'base_damage',
        };

        $isRingDamage = in_array(ItemType::RING->value, $types);
        $shouldIncludeDamageModifierDetails = ! $isRingDamage || ! is_null($this->equipped);

        $details['class_bonus_details'] = $shouldIncludeDamageModifierDetails ? $this->fetchClassBonusesEffecting('base_damage') : null;
        $details['boon_details'] = $shouldIncludeDamageModifierDetails ? $this->fetchBoonDetails('base_damage') : null;
        $details['class_specialties'] = $shouldIncludeDamageModifierDetails ? $this->fetchClassRankSpecialtiesDetails($classSpecialtyStat) : null;
        $details['ancestral_item_skill_data'] = $shouldIncludeDamageModifierDetails ? $this->fetchAncestralItemSkills('base_damage') : null;

        $typeAttributes = match (true) {
            in_array(ItemType::SPELL_DAMAGE->value, $types) => $this->character->getInformation()->getDamageBuilder()->buildSpellDamageBreakDownDetails($isVoided),
            in_array(ItemType::RING->value, $types) => $this->character->getInformation()->getDamageBuilder()->buildRingDamageBreakDown(),
            in_array(ItemType::SPELL_HEALING->value, $types) => $this->character->getInformation()->getHealingBuilder()->getHealingBuilder($isVoided),
            ! empty(array_intersect($types, ItemType::validWeapons())) => $this->character->getInformation()->getDamageBuilder()->buildWeaponDamageBreakDown($damageStatAmount, $isVoided),
            default => [],
        };

        return array_merge($details, $typeAttributes);
    }

    private function normalizeDamageTypes(string|array $types): array
    {
        if (is_array($types)) {
            return $types;
        }

        return [$types];
    }

    private function setNonEquippedDamageDetails(array $details, array $types, float $damageStatAmount): array
    {
        $percentage = 0.02;

        if (! empty(array_intersect($types, ItemType::validWeapons()))) {
            if ($this->character->classType()->isAlcoholic()) {
                $percentage = 0.25;
            } elseif ($this->character->classType()->isFighter()) {
                $percentage = 0.05;
            }
        }

        $value = $damageStatAmount * $percentage;

        $details['non_equipped_damage_amount'] = number_format(max($value, 5));
        $details['non_equipped_percentage_of_stat_used'] = $percentage;

        return $details;
    }

    private function fetchDamageOrHealingEquipmentBreakDown(array $types): array
    {
        if (in_array(ItemType::RING->value, $types)) {
            return [];
        }

        if (in_array(ItemType::SPELL_HEALING->value, $types)) {
            return array_values($this->fetchItemDetails('base_healing'));
        }

        return array_values($this->fetchItemDetails('base_damage'));
    }

    /**
     * Fetch Class Bonus Effecting the attribute.
     */
    private function fetchClassBonusesEffecting(string $attribute): ?array
    {
        $classBonusSkill = $this->character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->whereNotNull('game_class_id')
                    ->where('game_class_id', $this->character->game_class_id);
            })
            ->first();

        if (is_null($classBonusSkill)) {
            return null;
        }

        return [
            'name' => $classBonusSkill->baseSkill->name,
            'amount' => $classBonusSkill->{$attribute.'_mod'},
        ];
    }

    /**
     * Get map reductions that effect said stat.
     */
    private function getMapCharacterReductionsDetails(): ?array
    {
        $map = $this->character->map->gameMap;

        if (
            $map->mapType()->isHell() ||
            $map->mapType()->isPurgatory() ||
            $map->mapType()->isTwistedMemories()
        ) {
            return [
                'map_name' => $map->name,
                'reduction_amount' => $map->character_attack_reduction,
            ];
        }

        $purgatoryQuestItem = $this->character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::PURGATORY;
        })->first();

        if (! is_null($purgatoryQuestItem)) {

            if ($map->mapType()->isTheIcePlane() || $map->mapType()->isDelusionalMemories()) {
                return [
                    'map_name' => $map->name,
                    'reduction_amount' => $map->character_attack_reduction,
                ];
            }
        }

        return null;
    }

    /**
     * Fetch Ancestral Item Skill Details that effect the stat.
     */
    private function fetchAncestralItemSkills($stat): ?array
    {
        $artifact = ItemSkillAttribute::fetchArtifactItemEquipped($this->character);

        if (is_null($artifact)) {
            return null;
        }

        $itemSkills = ItemSkillAttribute::fetchItemSkillsThatEffectStat($artifact, $stat);

        if ($itemSkills->isEmpty()) {
            return null;
        }

        $details = [];

        foreach ($itemSkills as $itemSkill) {
            $details[] = [
                'name' => $itemSkill->itemSkill->name,
                'increase_amount' => $itemSkill->{$stat.'_mod'},
            ];
        }

        return $details;
    }

    /**
     * Fetch class ranks specialties details.
     */
    private function fetchClassRankSpecialtiesDetails(string $stat): ?array
    {
        $details = [];

        if ($this->character->damage_stat === $stat) {
            $classSpecialties = $this->character->classSpecialsEquipped
                ->where('equipped', '=', true)
                ->where('base_damage_stat_increase', '>', 0);

            foreach ($classSpecialties as $classSpecialty) {
                $details[] = [
                    'name' => $classSpecialty->gameClassSpecial->name,
                    'amount' => $classSpecialty->base_damage_stat_increase,
                ];
            }

            if (empty($details)) {
                return null;
            }

            return $details;
        }

        $modifier = $stat.'_mod';

        $classSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where($modifier, '>', 0);

        foreach ($classSpecialties as $classSpecialty) {
            $details[] = [
                'name' => $classSpecialty->gameClassSpecial->name,
                'amount' => $classSpecialty->{$modifier},
            ];
        }

        if (empty($details)) {
            return null;
        }

        return $details;
    }

    /**
     * Fetch class rank specialties that can effect your health.
     */
    private function fetchClassRankSpecialtiesForHealth(): ?array
    {
        $details = [];

        $classSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where('base_damage_stat_increase', '>', 0);

        foreach ($classSpecialties as $classSpecialty) {
            $details[] = [
                'name' => $classSpecialty->gameClassSpecial->name,
                'amount' => $classSpecialty->base_damage_stat_increase,
            ];
        }

        $healthSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where('health_mod', '>', 0);

        foreach ($healthSpecialties as $classSpecialty) {
            $details[] = [
                'name' => $classSpecialty->gameClassSpecial->name,
                'amount' => $classSpecialty->health_mod,
            ];
        }

        if (empty($details)) {
            return null;
        }

        return $details;
    }

    /**
     * Fetch boon details.
     */
    private function fetchBoonDetails(string $stat): ?array
    {
        $boonDetails = [];

        $characterBoons = $this->character->boons;

        $applyToAllStats = $characterBoons->where('itemUsed.increase_stat_by', '>', 0);
        $applyToSpecificStat = $characterBoons->where('itemUsed.'.$stat.'_mod', '>', 0);

        if ($applyToAllStats->isNotEmpty()) {

            $boonDetails['increases_all_stats'] = [];

            foreach ($applyToAllStats as $boon) {

                $boonDetails['increases_all_stats'][] = [
                    'item_details' => $this->getBasicDetailsOfItem($boon->itemUsed),
                    'increase_amount' => $boon->itemUsed->increase_stat_by,
                ];
            }
        }

        if ($applyToSpecificStat->isNotEmpty()) {

            $boonDetails['increases_single_stat'] = [];

            foreach ($applyToAllStats as $boon) {

                $boonDetails['increases_single_stat'][] = [
                    'item_details' => $this->getBasicDetailsOfItem($boon->itemUsed),
                    'increase_amount' => $boon->itemUsed->{$stat.'_mod'},
                ];
            }
        }

        if (empty($boonDetails)) {
            return null;
        }

        return $boonDetails;
    }

    /**
     * Fetch Item Details that effect the stat.
     */
    private function fetchItemDetails(string $stat, bool $isVoided = false): array
    {
        if (is_null($this->equipped)) {
            return [];
        }

        $details = [];

        foreach ($this->equipped as $slot) {
            $details[$slot->item->affix_name] = [];

            $details[$slot->item->affix_name]['item_base_stat'] = $slot->item->{$stat.'_mod'} ?? 0;
            $details[$slot->item->affix_name]['item_details'] = $this->getBasicDetailsOfItem($slot->item);
            $details[$slot->item->affix_name]['total_stat_increase'] = $isVoided ? 0 : $slot->item->holy_stack_stat_bonus;
            $details[$slot->item->affix_name]['attached_affixes'] = $isVoided ? [] : $this->fetchStatDetailsFromEquipment($slot->item, $stat)['attached_affixes'];
        }

        return $details;
    }

    /**
     * Fetch stat details from equipped items.
     */
    private function fetchStatDetailsFromEquipment(Item $item, string $stat): array
    {
        $details = [];

        $itemPrefix = $item->itemPrefix;
        $itemSuffix = $item->itemSuffix;

        $details['attached_affixes'] = [];

        if (! is_null($itemPrefix)) {

            $statAmount = $itemPrefix->{$stat.'_mod'};

            if ($statAmount > 0) {
                $details['attached_affixes'][] = [
                    'name' => $itemPrefix->name,
                    $stat.'_mod' => $itemPrefix->{$stat.'_mod'},
                ];
            }
        }

        if (! is_null($itemSuffix)) {
            $statAmount = $itemSuffix->{$stat.'_mod'};

            if ($statAmount > 0) {
                $details['attached_affixes'][] = [
                    'name' => $itemSuffix->name,
                    $stat.'_mod' => $itemSuffix->{$stat.'_mod'},
                ];
            }
        }

        return $details;
    }
}
