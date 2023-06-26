<?php

namespace App\Console\Commands;

use DivisionByZeroError;
use App\Flare\Models\ItemAffix;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use App\Flare\Jobs\RefactorSkillLevels;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Jobs\RefactorAttributes as JobsRefactorAttributes;

class RefactorAttributes extends Command {

    /**
     * @var integer|float|null $previousY
     */
    private int|float|null $previousY = null;

    /**
     * @var integer $maxValue
     */
    private int $maxValue = 2000000000;

    /**
     * @var integer $minValue
     */
    private int $minValue = 1000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refactor:attributes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refactor Atrributes';

    /**
     * Execute the console command.
     */
    public function handle() {

        ini_set('memory_limit', '-1');
        
        $curve = $this->generateCurve(56, 'cost', 'integer');

        dd($curve);

        $types = [
            'str_mod',
            'int_mod',
            'dex_mod',
            'chr_mod',
            'agi_mod',
            'focus_mod',
            'dur_mod',
            'str_reduction',
            'dur_reduction',
            'dex_reduction',
            'chr_reduction',
            'int_reduction',
            'agi_reduction',
            'focus_reduction',
            'class_bonus',
            'base_damage_mod',
            'base_ac_mod',
            'base_healing_mod',
            'skill_training_bonus',
            'skill_bonus',
            'skill_reduction',
            'resistance_reduction',
            'base_damage_mod_bonus',
            'base_healing_mod_bonus',
            'base_ac_mod_bonus',
            'fight_time_out_mod_bonus',
            'move_time_out_mod_bonus',
            'devouring_light',
            'entranced_chance',
            'steal_life_amount',
            'damage',
            'irresistible_damage',
            'damage_can_stack',
            'Weapon Crafting',
            'Armour Crafting',
            'Spell Crafting',
            'Ring Crafting',
            'Enchanting',
            'Accuracy',
            'Dodge',
            'Looting',
            'Casting Accuracy',
            'Soldier\'s Strength',
            'Shadow Dance',
            'Blood Lust',
            'Nature\'s Insight',
            'Alchemist\'s Concoctions',
            'Hell\'s Anvil',
            'Celestial Prayer',
            'Astral Magics',
            'Fighter\'s Resilience',
            'Incarcerated Thoughts'
        ];

        $jobs = [];

        
        foreach ($types as $type) {
            $itemAffixes       = $this->getItems($type);
            $attributesToFix   = $this->getAttributesToFix($itemAffixes->first());
            $itemAffixCount    = $itemAffixes->count();
            $skillLevelChanges = $this->generateSkillLevels($itemAffixCount);

            if ($itemAffixCount <= 1) {
                continue;
            }

            if (empty($attributesToFix)) {
                continue;
            }

            foreach ($attributesToFix as $attribute => $cast) {

                if ($attribute === 'skill_level_required' || $attribute === 'skill_level_trivial') {
                    continue;
                }

                if ($attribute === 'cost') {
                    $this->minValue = 1000;
                    $this->maxValue = 40000000000;
                }

                if ($attribute === 'int_required') {
                    $this->minValue = 10;
                    $this->maxValue = 1000000;
                }

                $curve = $this->generateCurve($itemAffixCount, $attribute, $cast);

                $this->previousY = null;

                $jobs[] = new JobsRefactorAttributes($itemAffixes->pluck('id')->toArray(), $curve, $attribute);

            }

            $jobs[] = new RefactorSkillLevels($itemAffixes->pluck('id')->toArray(), $skillLevelChanges);
        }

        Bus::chain($jobs)->dispatch();
    }

    /**
     * Get attributes who's cast type is integer or float and whos attribute is greator then 0
     *
     * @param ItemAffix $model
     * @return array
     */
    protected function getAttributesToFix(ItemAffix $model): array {
        $attributes = [];

        foreach ($model->getCasts() as $attribute => $castType) {
            if (($model->{$attribute}) && in_array($castType, ['integer', 'float'])) {
                $attributes[$attribute] = $castType;
            }
        }

        return $attributes;
    }

    /**
     * Get items based off the field.
     *
     * @param string $field
     * @return Collection
     */
    protected function getItems(string $field): Collection {
        $damageField  = 'damage';
        $booleanFields = ['irresistible_damage', 'damage_can_stack'];

        $builder = ItemAffix::where('randomly_generated', false);

        if ($damageField === $field) {
            return $builder->where($field, '>', 0)->get();
        }

        if (in_array($field, $booleanFields)) {
            return $builder->where($field, true)->get();
        }

        if (preg_match('/_/', $field)) {
            return $builder->where($field, '>', 0)->get();
        }

        return $builder->where('skill_name', $field)->get();
    }

    /**
     * generate the curve
     *
     * @param string $type
     * @param string $cast
     * @return array
     */
    protected function generateCurve(int $count, string $type, string $cast): array {

        $statModifiers = ['str_mod', 'dex_mod', 'agi_mod', 'int_mod', 'chr_mod', 'focus_mod', 'dur_mod', 'base_damage_mod', 'base_ac_mod', 'base_healing_mod'];

        $min       = $cast === 'integer' ? $this->minValue : 0.01;
        $max       = $this->maxValue(in_array($type, $statModifiers), $cast === 'integer');
        $increase  = $cast === 'integer' ? 100000 : 0.002;
        $range     = $cast === 'integer' ? $this->minValue/2: 0.20;

        // Generate numbers with a size of 20
        return $this->generateValues($count, $min, $max, $increase, $range, $cast === 'integer');
    }

    protected function generateSkillLevels($numItems) {
        $items = [];
        $skillLevelRequired = 1;
        $YO = 401; // Initial value for skill_level_trivial (Y intercept)
        $VO = 9; // Initial value for skill_level_trivial (Value at x=0)
        $k = 0.1; // Constant value for growth rate

        $scaleFactor = 0.1;

        for ($i = 1; $i <= $numItems; $i++) {
            $x = $i - 1; // Exponent value

            $skillLevelTrivial = $YO - $VO / ($k * ($scaleFactor * 0.8949));

            $skillLevelTrivial = abs(round($skillLevelTrivial));

            if ($skillLevelTrivial > $YO) {
                $skillLevelTrivial = $YO;
            }
            
            if ($skillLevelTrivial - $skillLevelRequired > 9) {
                $skillLevelTrivial = $skillLevelRequired + 9;
            }

            $items[] = [
                'skill_level_required' => $skillLevelRequired,
                'skill_level_trivial' => $skillLevelTrivial
            ];

            $previousSkillLevel = $skillLevelTrivial;

            if ($i == $numItems) {
                $skillLevelTrivial = 401; // Set the final skill_level_trivial to 401
                $items[] = [
                    'skill_level_required' => $previousSkillLevel,
                    'skill_level_trivial' => $skillLevelTrivial
                ];
            }

            $skillLevelRequired = $skillLevelTrivial;
            $scaleFactor += 0.1; // Increase the scaleFactor by 0.1 for each iteration
        }

        return $items;
    }

    /**
     * get max value
     *
     * @param boolean $statModifier
     * @param boolean $integer
     * @return integer|float
     */
    protected function maxValue(bool $statModifier = false, bool $integer = false): int|float {

        if ($integer) {
            return $this->maxValue;
        }

        if (!$integer && $statModifier) {
            return 2.0;
        }

        return 1.0;
    }

    /**
     * Generate the value.
     * 
     * Uses the forumal: y = YO - VO/k(i - e^kx)
     * 
     * This is an exponental growth where we contraol the rate of groth based on the amount of elements we need
     * to generate for.
     * 
     * @param integer $size
     * @param integer|float $YO
     * @param integer|float $i
     * @param string|null $type
     * @return void
     */
    public function generateValues(int $size, int|float $YO, int|float $i, int|float $increaseBy, int|float $range, bool $integer = false) {
        $numbers = array();
    
        for ($x = 0; $x < $size; $x++) {
            $y = $this->calculateY($x, $YO, $i, $increaseBy, $range, $size);

            if ($integer) {
                if ($y > 10000) {
                    $y = round(ceil($y), -2);
                }
            }

            $numbers[] = $y;
        }
    
        if ($integer) {
            if ($numbers[$size - 2] > $numbers[$size - 1]) {
                $numbers[$size - 2] = ($numbers[$size - 3] + $numbers[$size - 1]) / 2;
            }
        }
    
        return $numbers;
    }
    
    /**
     * Calculate for Y
     *
     * @param integer $x
     * @param integer|float $YO
     * @param integer|float $i
     * @param integer $size
     * @return void
     */
    public function calculateY(int $x, int|float $YO, int|float $i, int|float $increaseAmount, int|float $range, int $size): int|float
    {
        
        $growthRate = pow($i / $YO, 1 / ($size - 1));
    
        if ($x >= $size / 2) {
            $growthRate *= 1.0102;
        }
    
        $y = $YO * pow($growthRate, $x);
    
        if ($x === $size - 1) {
            $y = $i;
        } elseif ($y > ($i - $range)) {

            if (is_null($this->previousY)) {
                $this->previousY = $i - $range;
            } else {
                $this->previousY += $increaseAmount;
            }
            
            $y = $this->previousY + $increaseAmount;
        }
    
        return $y;
    }
}
