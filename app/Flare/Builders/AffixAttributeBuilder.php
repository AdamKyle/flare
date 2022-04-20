<?php

namespace App\Flare\Builders;

use App\Flare\Values\RandomAffixDetails;
use Illuminate\Support\Collection;

class AffixAttributeBuilder {

    /**
     * @var array $percentageRange
     */
    private $percentageRange;

    /**
     * @var array $damageRange
     */
    private $damageRange;

    /**
     * @var Collection $characterSkills
     */
    private $characterSkills;

    /**
     * @param array $range
     * @return $this
     */
    public function setPercentageRange(array $range): AffixAttributeBuilder {
        $this->percentageRange = $range;

        return $this;
    }

    /**
     * @param array $range
     * @return $this
     */
    public function setDamageRange(array $range): AffixAttributeBuilder {
        $this->damageRange = $range;

        return $this;
    }

    /**
     * @param Collection $skills
     * @return AffixAttributeBuilder
     */
    public function setCharacterSkills(Collection $skills): AffixAttributeBuilder {
        $this->characterSkills = $skills;

        return $this;
    }

    /**
     * Build the attributes for the affix.
     *
     * @param string $type
     * @param int $amountPaid
     * @return array
     */
    public function buildAttributes(string $type, int $amountPaid): array {
        $attributes = [];

        if ($this->increasesStats()) {
            $attributes = $this->mergeDetails($attributes, $this->increaseStats());
        }

        if ($this->reducesEnemyStats()) {
            $attributes = $this->mergeDetails($attributes, $this->reduceEnemyStats());
        }

        if ($this->canStealLife()) {
            $attributes = $this->mergeDetails($attributes, $this->setLifeStealingAmount());
        }

        if ($this->canEntrance()) {
            $attributes = $this->mergeDetails($attributes, $this->setEntrancingAmount());
        }

        if ($this->canIncreaseCoreModifiers()) {
            $attributes = $this->mergeDetails($attributes, $this->setCoreModifiers());
        }

        $attributes = $this->mergeDetails($attributes, $this->setDamageDetails());

        $attributes = $this->mergeDetails($attributes, $this->setClassBonus());
        $attributes = $this->mergeDetails($attributes, $this->setReductions());

        if ($this->canHaveSkill()) {
            $attributes = $this->mergeDetails($attributes, $this->setSkillDetails());
        }

        if ($this->canHaveSkillBonuses()) {
            $attributes = $this->mergeDetails($attributes, $this->setSkillBonuses());
        }

        if ($this->canHaveDevouringLight()) {
            $attributes = $this->mergeDetails($attributes, $this->setDevouringLight());
        }

        return $this->mergeDetails($attributes, $this->setBaseDetails($type, $amountPaid));
    }

    public function setBaseDetails(string $type, int $amountPaid): array {
        $names = RandomAffixDetails::names();

        return [
            'name'                 => $names[rand(0, count($names) - 1)],
            'type'                 => $type,
            'description'          => 'This is a randomly generated affix. By speaking with the Queen of Hearts in Hell, you can re-roll specific stats for it\'s cost plus the appropriate shard cost.
                All attributes are randomly rolled each time you get an item via completing faction achievements or from purchasing from the Queen of Hearts. These affixes cannot be crafted. But can be sold on the market.
                The Queen of Hearts will also let you switch the affix to another item, for the affix and shard cost. For more info, see help docs under NPCs for more details.
                You can also check out the help docs under crafting/enchanting to read more about randomly generated affixes.',
            'cost'                 => $amountPaid,
            'int_required'         => '0',
            'skill_level_required' => '999',
            'skill_level_trivial'  => '999',
            'can_drop'             => false,
            'randomly_generated'   => true,
        ];
    }

    public function increaseStats() {
        $stats = [
            'str_mod',
            'dur_mod',
            'dex_mod',
            'chr_mod',
            'int_mod',
            'agi_mod',
            'focus_mod',
        ];

        $statAttributes = [];

        foreach ($stats as $stat) {
            $statAttributes[$stat] = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]);
        }

        return $statAttributes;
    }

    public function reduceEnemyStats() {
        $stats = [
            'str_reduction',
            'dur_reduction',
            'dex_reduction',
            'chr_reduction',
            'int_reduction',
            'agi_reduction',
            'focus_reduction',
        ];

        $statAttributes = [];

        foreach ($stats as $stat) {
            $statAttributes[$stat] = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]);
        }

        $statAttributes['reduces_enemy_stats'] = true;

        return $statAttributes;
    }

    public function setLifeStealingAmount(): array {
        return [
            'steal_life_amount' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 10,
        ];
    }

    public function setEntrancingAmount(): array {
        return [
            'entranced_chance' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 10,
        ];
    }

    public function setCoreModifiers(): array {
        return [
            'base_damage_mod'  => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
            'base_ac_mod'      => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
            'base_healing_mod' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
        ];
    }

    public function setDamageDetails(): array {
        return [
            'damage'              => rand($this->damageRange[0], $this->damageRange[1]),
            'irresistible_damage' => $this->canHave(),
            'damage_can_stack'    =>  $this->canHave(),
        ];
    }

    public function setClassBonus(): array {
        return [
            'class_bonus' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 10
        ];
    }

    public function setReductions(): array {
        return [
            'skill_reduction'      => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
            'resistance_reduction' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
        ];
    }

    public function setSkillDetails(): array {
        return [
            'skill_name'              => $this->characterSkills[rand(0, count($this->characterSkills) - 1)]->baseSkill->name,
            'skill_training_bonus'    => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 10,
            'skill_bonus'             => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 10,
        ];
    }

    public function setSkillBonuses(): array {
        $allowedTypes = [
            1,2,3,4,5,6,7,13 // The match to the SkillTypeValue const's
        ];

        return [
            'affects_skill_type'       => $allowedTypes[rand(0, count($allowedTypes) - 1)],
            'base_damage_mod_bonus'    => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
            'base_healing_mod_bonus'   => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
            'base_ac_mod_bonus'        => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
            'fight_time_out_mod_bonus' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
            'move_time_out_mod_bonus'  => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]),
        ];
    }

    public function setDevouringLight(): array {
        return [
            'devouring_light' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 10
        ];
    }

    protected function mergeDetails(array $details, array $additionalDetails): array {
        return array_merge($details, $additionalDetails);
    }

    protected function increasesStats(): bool {
        return $this->canHave();
    }

    protected function reducesEnemyStats(): bool {
        return $this->canHave();
    }

    protected function canStealLife(): bool {
        return $this->canHave();
    }

    protected function canEntrance(): bool {
        return $this->canHave();
    }

    protected function canIncreaseCoreModifiers(): bool {
        return $this->canHave();
    }

    protected function canHaveSkill(): bool {
        return $this->canHave();
    }

    protected function canHaveSkillBonuses(): bool {
        return $this->canHave();
    }

    protected function canHaveDevouringLight(): bool {
        return $this->canHave();
    }

    protected function canHave(): bool {
        return rand(1, 100) > 50;
    }

    protected function getPercentage(int $min, int $max): float {
        return ($min + mt_rand() / mt_getrandmax() * ($max - $min)) / 100;
    }
}
