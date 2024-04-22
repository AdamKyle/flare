<?php

namespace App\Game\Character\Builders\StatDetailsBuilder;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder;
use App\Game\Character\Builders\StatDetailsBuilder\Concerns\BasicItemDetails;
use App\Game\Character\Concerns\FetchEquipped;
use Facades\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ItemSkillAttribute;
use Illuminate\Support\Collection;

class StatModifierDetails {

    use FetchEquipped, BasicItemDetails;

    /**
     * @var Collection|null $equipped
     */
    private Collection|null $equipped = null;

    /**
     * @var Character|null $character
     */
    private ?Character $character = null;

    /**
     * Set the character.
     *
     * @param Character $character
     * @return $this
     */
    public function setCharacter(Character $character): StatModifierDetails {

        $this->character = $character;

        $this->equipped = $this->fetchEquipped($character);

        return $this;
    }

    /**
     * Get stat details for a specific stat.
     *
     * @param string $stat
     * @return array
     */
    public function forStat(string $stat): array {
        $details = [];

        $details['base_value'] = number_format($this->character->{$stat});
        $details['items_equipped'] = array_values($this->fetchItemDetails($stat));
        $details['boon_details'] = $this->fetchBoonDetails($stat);
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesDetails($stat);
        $details['ancestral_item_skill_data'] = $this->fetchAncestralItemSkills($stat);
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();

        return $details;
    }

    public function buildSpecificBreakDown(string $type, bool $isVodied): array {
        switch($type) {
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
     *
     * @param bool $isVoided
     * @return array
     */
    public function fetchHealthBreakDown(bool $isVoided): array {
        $details = [];

        $details['stat_amount'] = number_format($this->character->getInformation()->statMod('dur', $isVoided));
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesForHealth();

        return $details;
    }

    /**
     * Build Defence Details.
     *
     * @param bool $isVoided
     * @return array
     */
    public function buildDefenceBreakDown(bool $isVoided): array {

        $details = [];

        $details['class_bonus_details']       = $this->fetchClassBonusesEffecting('base_ac');
        $details['boon_details']              = $this->fetchBoonDetails('base_ac');
        $details['class_specialties']         = $this->fetchClassRankSpecialtiesDetails('base_ac');
        $details['ancestral_item_skill_data'] = $this->fetchAncestralItemSkills('base_ac');

        return array_merge($details, $this->character->getInformation()->getDefenceBuilder()->buildDefenceBreakDownDetails($isVoided));
    }

    public function buildDamageBreakDown(string $type, bool $isVoided): array {
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
            if ($type === 'weapon') {

                if ($this->character->classType()->isAlcoholic()) {
                    $value = $damageStatAmount * 0.25;

                    $details['non_equipped_damage_amount'] = number_format(max($value, 5));
                    $details['non_equipped_percentage_of_stat_used'] = 0.25;
                } else if ($this->character->classType()->isFighter()) {
                    $value = $damageStatAmount * 0.05;

                    $details['non_equipped_damage_amount'] = number_format(max($value, 5));
                    $details['non_equipped_percentage_of_stat_used'] = 0.05;
                } else {
                    $value = $damageStatAmount * 0.02;

                    $details['non_equipped_damage_amount'] = number_format(max($value, 5));
                    $details['non_equipped_percentage_of_stat_used'] = 0.02;
                }
            }

            if ($type === 'spell_damage' && $this->character->classType()->isHeretic()) {

                $value = $damageStatAmount * 0.15;

                $details['spell_damage_stat_amount_to_use'] = number_format(max($value, 5));
                $details['percentage_of_stat_used'] = 0.15;
            }
        }

        $details['class_bonus_details']       = $type === 'ring' ? null : $this->fetchClassBonusesEffecting('base_damage');
        $details['boon_details']              = $type === 'ring' ? null : $this->fetchBoonDetails('base_damage');
        $details['class_specialties']         = $type === 'ring' ? null : $this->fetchClassRankSpecialtiesDetails('base_damage');
        $details['ancestral_item_skill_data'] = $this->fetchAncestralItemSkills('base_damage');

        $typeAttributes = [];

        switch ($type) {
            case 'weapon':
                $typeAttributes = $this->character->getInformation()->getDamageBuilder()->buildWeaponDamageBreakDown($damageStatAmount, $isVoided);
                break;
            case 'spell-damage':
                $typeAttributes = $this->character->getInformation()->getDamageBuilder()->buildSpellDamageBreakDownDetails($isVoided);
                break;
            case 'ring':
                $typeAttributes = $this->character->getInformation()->getDamageBuilder()->buildRingDamageBreakDown();
                break;
            case 'spell-healing':
                $typeAttributes = $this->character->getInformation()->getHealingBuilder()->getHealingBuilder($isVoided);
            default:
                break;
        }

        return array_merge($details, $typeAttributes);
    }

    /**
     * Fetch Class Bonus Effecting the attribute.
     *
     * @param string $attribute
     * @return array|null
     */
    private function fetchClassBonusesEffecting(string $attribute): array | null {
        $classBonusSkill = $this->character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->whereNotNull('game_class_id');
            })
            ->first();

        if (is_null($classBonusSkill)) {
            return null;
        }

        if ($classBonusSkill->game_class_id !== $this->character->game_class_id) {
            return null;
        }

        return [
            'name' => $classBonusSkill->baseSkill->name,
            'amount' => $classBonusSkill->{$attribute . '_mod'}
        ];
    }

    /**
     * Get map reductions that effect said stat.
     *
     * @return array|null
     */
    private function getMapCharacterReductionsDetails(): array | null {
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

        $purgatoryQuestItem = $this->character->inventory->slots->filter(function($slot) {
            return $slot->item->effect === ItemEffectsValue::PURGATORY;
        })->first();

        if (!is_null($purgatoryQuestItem)) {

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
     *
     * @param $stat
     * @return array|null
     */
    private function fetchAncestralItemSkills($stat): array | null {
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
                'increase_amount' => $itemSkill->{$stat . '_mod'},
            ];
        }

        return $details;
    }

    /**
     * Fetch class ranks specialties details.
     *
     * @param string $stat
     * @return array|null
     */
    private function fetchClassRankSpecialtiesDetails(string $stat): array | null {

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

            return $details;
        }

        $classSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where($stat . '_mod', '>', 0);


        foreach ($classSpecialties as $classSpecialty) {
            $details[] = [
                'name' => $classSpecialty->gameClassSpecial->name,
                'amount' => $classSpecialty->{$stat . '_mod'},
            ];
        }

        if (empty($details)) {
            return null;
        }

        return $details;
    }

    /**
     * Fetch class rank specialties that can effect your health.
     *
     * @return array|null
     */
    private function fetchClassRankSpecialtiesForHealth(): array | null {
        $classSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where('base_damage_stat_increase', '>', 0);

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
     *
     * @param string $stat
     * @return array|null
     */
    private function fetchBoonDetails(string $stat): array|null {
        $boonDetails = [];

        $characterBoons = $this->character->boons;

        $applyToAllStats = $characterBoons->where('itemUsed.increase_stat_by', '>', 0);
        $applyToSpecificStat = $characterBoons->where('itemUsed.' . $stat . '_mod', '>', 0);

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
                    'increase_amount' => $boon->itemUsed->{$stat . '_mod'},
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
     *
     * @param string $stat
     * @return array
     */
    private function fetchItemDetails(string $stat): array {
        if (is_null($this->equipped)) {
            return [];
        }

        $details = [];

        foreach ($this->equipped as $slot) {
            $details[$slot->item->affix_name] = [];

            $details[$slot->item->affix_name]['item_base_stat'] = $slot->item->{$stat . '_mod'} ?? 0;
            $details[$slot->item->affix_name]['item_details'] = $this->getBasicDetailsOfItem($slot->item);
            $details[$slot->item->affix_name]['attached_affixes'] = $this->fetchStatDetailsFromEquipment($slot->item, $stat)['attached_affixes'];
        }

        return $details;
    }

    /**
     * Fetch stat details from equipped items.
     *
     * @param Item $item
     * @param string $stat
     * @return array
     */
    private function fetchStatDetailsFromEquipment(Item $item, string $stat): array {
        $details = [];

        $itemPrefix = $item->itemPrefix;
        $itemSuffix = $item->itemSuffix;

        $details['attached_affixes'] = [];

        if (!is_null($itemPrefix)) {

            $statAmount = $itemPrefix->{$stat . '_mod'};

            if ($statAmount > 0) {
                $details['attached_affixes'][] = [
                    'affix_name' => $itemPrefix->name,
                    $stat . '_mod' => $itemPrefix->{$stat . '_mod'}
                ];
            }
        }

        if (!is_null($itemSuffix)) {
            $statAmount = $itemSuffix->{$stat . '_mod'};

            if ($statAmount > 0) {
                $details['attached_affixes'][] = [
                    'affix_name' => $itemSuffix->name,
                    $stat . '_mod' => $itemSuffix->{$stat . '_mod'}
                ];
            }
        }

        return $details;
    }
}
