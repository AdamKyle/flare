<?php

namespace App\Flare\Builders;

use App\Flare\Values\RandomAffixDetails;
use Illuminate\Support\Collection;

class AffixAttributeBuilder
{
    /**
     * @var array
     */
    private $percentageRange;

    /**
     * @var array
     */
    private $damageRange;

    /**
     * @var Collection
     */
    private $characterSkills;

    /**
     * @return $this
     */
    public function setPercentageRange(array $range): AffixAttributeBuilder
    {
        $this->percentageRange = $range;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDamageRange(array $range): AffixAttributeBuilder
    {
        $this->damageRange = $range;

        return $this;
    }

    public function setCharacterSkills(Collection $skills): AffixAttributeBuilder
    {
        $this->characterSkills = $skills;

        return $this;
    }

    /**
     * Build the attributes for the affix.
     */
    public function buildAttributes(string $type, int $amountPaid, bool $ignoreSkills = false): array
    {
        $attributes = [];

        if ($this->increasesStats()) {
            $attributes = $this->mergeDetails($attributes, $this->increaseStats($amountPaid));
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

        $attributes = $this->mergeDetails($attributes, $this->setReductions());

        if (! $ignoreSkills) {
            if ($this->canHaveSkill()) {
                $attributes = $this->mergeDetails($attributes, $this->setSkillDetails());
            }

            if ($this->canHaveSkillBonuses()) {
                $attributes = $this->mergeDetails($attributes, $this->setSkillBonuses());
            }
        } else {
            $attributes = $this->mergeDetails($attributes, $this->setSkillBonuses());
        }

        if ($this->canHaveDevouringLight()) {
            $attributes = $this->mergeDetails($attributes, $this->setDevouringLight());
        }

        return $this->mergeDetails($attributes, $this->setBaseDetails($type, $amountPaid));
    }

    public function setBaseDetails(string $type, int $amountPaid): array
    {
        $names = RandomAffixDetails::names();

        return [
            'name' => $names[rand(0, count($names) - 1)],
            'type' => $type,
            'description' => 'This is a randomly generated affix. By speaking with the Queen of Hearts in Hell, you can re-roll specific stats for it\'s cost plus the appropriate shard cost.
                All attributes are randomly rolled each time you get an item via completing faction achievements or from purchasing from the Queen of Hearts. These affixes cannot be crafted. But can be sold on the market.
                The Queen of Hearts will also let you switch the affix to another item, for the affix and shard cost. For more info, see help docs under NPCs for more details.
                You can also check out the help docs under crafting/enchanting to read more about randomly generated affixes.',
            'cost' => $amountPaid,
            'int_required' => '0',
            'skill_level_required' => '999',
            'skill_level_trivial' => '999',
            'can_drop' => false,
            'randomly_generated' => true,
        ];
    }

    public function increaseStats()
    {
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
            $amount = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]);

            $amount = $amount / 100;

            $statAttributes[$stat] = $amount;
        }

        return $statAttributes;
    }

    public function reduceEnemyStats()
    {
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
            $amount = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100;
            $statAttributes[$stat] = $amount > 1 ? .95 : $amount;
        }

        $statAttributes['reduces_enemy_stats'] = true;

        return $statAttributes;
    }

    public function setLifeStealingAmount(): array
    {
        $amount = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100;

        return [
            'steal_life_amount' => $amount > 1 ? 1 : $amount,
        ];
    }

    public function setEntrancingAmount(): array
    {
        $amount = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100;

        return [
            'entranced_chance' => $amount > 1 ? 1 : $amount,
        ];
    }

    public function setCoreModifiers(): array
    {
        return [
            'base_damage_mod' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100,
            'base_ac_mod' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100,
            'base_healing_mod' => $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100,
        ];
    }

    public function setDamageDetails(): array
    {
        return [
            'damage_amount' => rand($this->damageRange[0], $this->damageRange[1]) / 100,
            'irresistible_damage' => $this->canHave(),
            'damage_can_stack' => $this->canHave(),
        ];
    }

    public function setReductions(): array
    {
        $amount = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100;

        return [
            'skill_reduction' => $amount > 1 ? .95 : $amount,
            'resistance_reduction' => $amount > 1 ? .95 : $amount,
        ];
    }

    public function setSkillDetails(): array
    {
        $amount = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100;

        return [
            'skill_name' => $this->characterSkills[rand(0, count($this->characterSkills) - 1)]->baseSkill->name,
            'skill_training_bonus' => $amount > 1 ? 1 : $amount,
            'skill_bonus' => $amount > 1 ? 1 : $amount,
        ];
    }

    public function setSkillBonuses(): array
    {
        $allowedTypes = [
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            13, // The match to the SkillTypeValue const's
        ];

        return [
            'affects_skill_type' => $allowedTypes[rand(0, count($allowedTypes) - 1)],
        ];
    }

    public function setDevouringLight(): array
    {
        $amount = $this->getPercentage($this->percentageRange[0], $this->percentageRange[1]) / 100;

        return [
            'devouring_light' => $amount > 1 ? .95 : $amount,
        ];
    }

    protected function mergeDetails(array $details, array $additionalDetails): array
    {
        return array_merge($details, $additionalDetails);
    }

    protected function increasesStats(): bool
    {
        return $this->canHave();
    }

    protected function reducesEnemyStats(): bool
    {
        return $this->canHave();
    }

    protected function canStealLife(): bool
    {
        return $this->canHave();
    }

    protected function canEntrance(): bool
    {
        return $this->canHave();
    }

    protected function canIncreaseCoreModifiers(): bool
    {
        return $this->canHave();
    }

    protected function canHaveSkill(): bool
    {
        return $this->canHave();
    }

    protected function canHaveSkillBonuses(): bool
    {
        return $this->canHave();
    }

    protected function canHaveDevouringLight(): bool
    {
        return $this->canHave();
    }

    protected function canHave(): bool
    {
        return rand(1, 100) > 50;
    }

    protected function getPercentage(int $min, int $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
