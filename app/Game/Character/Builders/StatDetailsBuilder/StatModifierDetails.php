<?php

namespace App\Game\Character\Builders\StatDetailsBuilder;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Character\Concerns\FetchEquipped;
use Facades\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ItemSkillAttribute;
use Illuminate\Support\Collection;

class StatModifierDetails {

    use FetchEquipped, IsItemUnique;

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

        $details['base_value'] = $this->character->{$stat};
        $details['items_equipped'] = array_values($this->fetchItemDetails($stat));
        $details['boon_details'] = $this->fetchBoonDetails($stat);
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesDetails($stat);
        $details['ancestral_item_skill_data'] = $this->fetchAncestralItemSkills($stat);
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();

        return $details;
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

        if ($this->character->damage_stat === $stat) {
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

            return $details;
        }

        return null;
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

    /**
     * Create basic item details.
     *
     * @param Item $item
     * @return array
     */
    private function getBasicDetailsOfItem(Item $item): array {
        return [
            'name' => $item->affix_name,
            'type' => $item->type,
            'affix_count' => $item->affix_count,
            'is_unique' => $this->isUnique($item),
            'holy_stacks_applied' => $item->holy_stacks_applied,
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
        ];
    }
}
