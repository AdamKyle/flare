<?php

namespace App\Game\Character\Builders\StatDetailsBuilder;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Builders\StatDetailsBuilder\Concerns\BasicItemDetails;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Character\Concerns\FetchEquipped;
use Facades\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ItemSkillAttribute;
use Illuminate\Support\Collection;

class StatModifierDetails
{
    use BasicItemDetails, FetchEquipped;

    private ?Collection $equipped = null;

    private ?Character $character = null;

    public function __construct( private readonly CharacterStatBuilder $characterStatBuilder){
    }

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
                return $this->buildDamageBreakDown('weapon', $isVodied);
            case 'spell_damage':
                return $this->buildDamageBreakDown('spell-damage', $isVodied);
            case 'ring_damage':
                return $this->buildDamageBreakDown('ring', $isVodied);
            case 'heal_for':
                return $this->buildDamageBreakDown('spell-healing', $isVodied);
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

        $details['stat_amount'] = number_format($this->character->getInformation()->statMod('dur', $isVoided));
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesForHealth();

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

        return array_merge($details, $this->character->getInformation()->getDefenceBuilder()->buildDefenceBreakDownDetails($isVoided));
    }

    public function buildDamageBreakDown(string $type, bool $isVoided): array
    {
        $details = [];

        $damageStatAmount = $this->character->getInformation()->statMod($this->character->damage_stat, $isVoided);

        $details['damage_stat_name'] = $this->character->damage_stat;
        $details['damage_stat_amount'] = number_format($this->character->getInformation()->statMod($this->character->damage_stat, $isVoided));
        $details['non_equipped_damage_amount'] = 0;
        $details['non_equipped_percentage_of_stat_used'] = 0;
        $details['spell_damage_stat_amount_to_use'] = 0;
        $details['percentage_of_stat_used'] = 0;
        $details['total_damage_for_type'] = number_format($this->character->getInformation()->buildDamage($type, $isVoided));
        $details['base_damage'] = 0;

        $equipped = $this->fetchEquipped($this->character);

        if (is_null($equipped)) {
            if (in_array($type, ItemType::validWeapons())) {

                if ($this->character->classType()->isAlcoholic()) {
                    $value = $damageStatAmount * 0.25;

                    $details['non_equipped_damage_amount'] = number_format(max($value, 5));
                    $details['non_equipped_percentage_of_stat_used'] = 0.25;
                } elseif ($this->character->classType()->isFighter()) {
                    $value = $damageStatAmount * 0.05;

                    $details['non_equipped_damage_amount'] = number_format(max($value, 5));
                    $details['non_equipped_percentage_of_stat_used'] = 0.05;
                } else {
                    $value = $damageStatAmount * 0.02;

                    $details['non_equipped_damage_amount'] = number_format(max($value, 5));
                    $details['non_equipped_percentage_of_stat_used'] = 0.02;
                }
            }

            if ($type === ItemType::SPELL_DAMAGE->value && $this->character->classType()->isHeretic()) {

                $value = $damageStatAmount * 0.15;

                $details['spell_damage_stat_amount_to_use'] = number_format(max($value, 5));
                $details['percentage_of_stat_used'] = 0.15;
            }
        }

        $details['class_bonus_details'] = $type === 'ring' ? null : $this->fetchClassBonusesEffecting('base_damage');
        $details['boon_details'] = $type === 'ring' ? null : $this->fetchBoonDetails('base_damage');
        $details['class_specialties'] = $type === 'ring' ? null : $this->fetchClassRankSpecialtiesDetails('base_damage');
        $details['ancestral_item_skill_data'] = $type === 'ring' ? null : $this->fetchAncestralItemSkills('base_damage');

        $typeAttributes = match (true) {
            in_array($type, ItemType::validWeapons()) => $this->character->getInformation()->getDamageBuilder()->buildWeaponDamageBreakDown($damageStatAmount, $isVoided),
            $type === ItemType::SPELL_DAMAGE->value => $this->character->getInformation()->getDamageBuilder()->buildSpellDamageBreakDownDetails($isVoided),
            $type === ItemType::RING->value => $this->character->getInformation()->getDamageBuilder()->buildRingDamageBreakDown(),
            $type === ItemType::SPELL_HEALING->value => $this->character->getInformation()->getHealingBuilder()->getHealingBuilder($isVoided),
            default => [],
        };


        return array_merge($details, $typeAttributes);
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

        if ($map->mapType()->isHell() ||
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

        $classSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where($stat.'_mod', '>', 0);

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

    /**
     * Fetch class rank specialties that can effect your health.
     */
    private function fetchClassRankSpecialtiesForHealth(): ?array
    {
        $classSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where('base_damage_stat_increase', '>', 0);

        $healthSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where('health_mod', '>', 0);

        $classSpecialties = $classSpecialties->merge($healthSpecialties);

        $details = [];

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
    private function fetchItemDetails(string $stat): array
    {
        if (is_null($this->equipped)) {
            return [];
        }

        $details = [];

        foreach ($this->equipped as $slot) {
            $details[$slot->item->affix_name] = [];

            $details[$slot->item->affix_name]['item_base_stat'] = $slot->item->{$stat.'_mod'} ?? 0;
            $details[$slot->item->affix_name]['item_details'] = $this->getBasicDetailsOfItem($slot->item);
            $details[$slot->item->affix_name]['total_stat_increase'] = $slot->item->holy_stack_stat_bonus;
            $details[$slot->item->affix_name]['attached_affixes'] = $this->fetchStatDetailsFromEquipment($slot->item, $stat)['attached_affixes'];
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
                    'affix_name' => $itemPrefix->name,
                    $stat.'_mod' => $itemPrefix->{$stat.'_mod'},
                ];
            }
        }

        if (! is_null($itemSuffix)) {
            $statAmount = $itemSuffix->{$stat.'_mod'};

            if ($statAmount > 0) {
                $details['attached_affixes'][] = [
                    'affix_name' => $itemSuffix->name,
                    $stat.'_mod' => $itemSuffix->{$stat.'_mod'},
                ];
            }
        }

        return $details;
    }
}
