<?php

namespace App\Flare\Values;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;

class ItemAffixType {

    /**
     * @var string $value
     */
    private $value;

    const EFFECTS_STR                   = 'effects-str';
    const EFFECTS_DEX                   = 'effects-dex';
    const EFFECTS_AGI                   = 'effects-agi';
    const EFFECTS_DUR                   = 'effects-dur';
    const EFFECTS_INT                   = 'effects-int';
    const EFFECTS_FOCUS                 = 'effects-focus';
    const EFFECTS_SKILL_XP              = 'skill-xp';
    const EFFECTS_SKILL_BONUS           = 'skill-bonus';
    const EFFECTS_SKILL_REDUCTION       = 'enemy-skill-reduction';
    const EFFECTS_CLASS_BONUS           = 'class-bonus';
    const EFFECTS_BASE_DAMAGE_MOD       = 'base-damage';
    const EFFECTS_BASE_HEALING_MOD      = 'base-healing';
    const EFFECTS_BASE_AC_MOD           = 'base-ac';
    const EFFECTS_FIGHT_TIMEOUT         = 'fight-time-out';
    const EFFECTS_MOVEMENT_TIMEOUT      = 'move-time-out';
    const REDUCES_ENEMY_STR             = 'reduce-enemy-str';
    const REDUCES_ENEMY_DEX             = 'reduce-enemy-dex';
    const REDUCES_ENEMY_AGI             = 'reduce-enemy-agi';
    const REDUCES_ENEMY_DUR             = 'reduce-enemy-dur';
    const REDUCES_ENEMY_INT             = 'reduce-enemy-int';
    const REDUCES_ENEMY_FOCUS           = 'reduce-enemy-focus';
    const REDUCE_ENEMY_RESISTANCE       = 'reduce-enemy-resistance';
    const LIFE_STEALING                 = 'life-stealing';
    const IRRESISTIBLE_DAMAGE           = 'irresistible-damage';
    const RESISTIBLE_DAMAGE             = 'resistible-damage';
    const ENTRANCING                    = 'entrancing';
    const DEVOURING_LIGHT               = 'devouring-light';

    /**
     * @var string[]
     */
    protected static $values = [
        self::EFFECTS_STR                   => 'effects-str',
        self::EFFECTS_DEX                   => 'effects-dex',
        self::EFFECTS_AGI                   => 'effects-agi',
        self::EFFECTS_DUR                   => 'effects-dur',
        self::EFFECTS_INT                   => 'effects-int',
        self::EFFECTS_FOCUS                 => 'effects-focus',
        self::EFFECTS_SKILL_XP              => 'skill-xp',
        self::EFFECTS_SKILL_BONUS           => 'skill-bonus',
        self::EFFECTS_SKILL_REDUCTION       => 'enemy-skill-reduction',
        self::EFFECTS_CLASS_BONUS           => 'class-bonus',
        self::EFFECTS_BASE_DAMAGE_MOD       => 'base-damage',
        self::EFFECTS_BASE_HEALING_MOD      => 'base-healing',
        self::EFFECTS_BASE_AC_MOD           => 'base-ac',
        self::EFFECTS_FIGHT_TIMEOUT         => 'fight-time-out',
        self::EFFECTS_MOVEMENT_TIMEOUT      => 'move-time-out',
        self::REDUCES_ENEMY_STR             => 'reduce-enemy-str',
        self::REDUCES_ENEMY_DEX             => 'reduce-enemy-dex',
        self::REDUCES_ENEMY_AGI             => 'reduce-enemy-agi',
        self::REDUCES_ENEMY_DUR             => 'reduce-enemy-dur',
        self::REDUCES_ENEMY_INT             => 'reduce-enemy-int',
        self::REDUCES_ENEMY_FOCUS           => 'reduce-enemy-focus',
        self::REDUCE_ENEMY_RESISTANCE       => 'reduce-enemy-resistance',
        self::LIFE_STEALING                 => 'life-stealing',
        self::IRRESISTIBLE_DAMAGE           => 'irresistible-damage',
        self::RESISTIBLE_DAMAGE             => 'resistible-damage',
        self::ENTRANCING                    => 'entrancing',
        self::DEVOURING_LIGHT               => 'devouring-light',
    ];

    /**
     * For the affixes live-wire table.
     *
     * @var string[]
     */
    public static $dropDownValues = [
        'effects-str'             => 'Effects Strength',
        'effects-dex'             => 'Effects Dexterity',
        'effects-agi'             => 'Effects Agility',
        'effects-dur'             => 'Effects Durability',
        'effects-int'             => 'Effects Intelligence',
        'effects-focus'           => 'Effects Focus',
        'skill-xp'                => 'Effects Skill XP % Gain',
        'skill-bonus'             => 'Effects Skill Bonus %',
        'enemy-skill-reduction'   => 'Effects Skill Reduction on Enemies %',
        'class-bonus'             => 'Effects Class Bonus %',
        'base-damage'             => 'Effects Base Damage Mod %',
        'base-healing'            => 'Effects Base Healing Mod %',
        'base-ac'                 => 'Effects Base Armour Class %',
        'fight-time-out'          => 'Effects Fight Timeout Modifier %',
        'move-time-out'           => 'Effects Movement Timeout Modifier %',
        'reduce-enemy-str'        => 'Reduces Enemies Strength',
        'reduce-enemy-dex'        => 'Reduces Enemies Dexterity',
        'reduce-enemy-agi'        => 'Reduces Enemies Agility',
        'reduce-enemy-dur'        => 'Reduces Enemies Durability',
        'reduce-enemy-int'        => 'Reduces Enemies Intelligence',
        'reduce-enemy-focus'      => 'Reduces Enemies Focus',
        'reduce-enemy-resistance' => 'Reduces Enemies Resistance',
        'life-stealing'           => 'Steals Life from Enemies',
        'irresistible-damage'     => 'Non Resistant Damage Affixes',
        'resistible-damage'       => 'Resistible Damage Affixes',
        'entrancing'              => 'Entrancing Affixes',
        'devouring-light'         => 'Effects Devouring Light %',
    ];

    /**
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(string $value)
    {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function getQuery(Builder $query): Builder {
        switch($this->value) {
            case 'effects-str':
            case 'effects-dex':
            case 'effects-agi':
            case 'effects-dur':
            case 'effects-int':
            case 'effects-focus':
                return $this->fetchSpecificColumn($query,explode('-', $this->value)[1] . '_mod');
            case 'skill-xp':
                return $this->fetchSpecificColumn($query, 'skill_training_bonus');
            case 'skill-bonus':
                return $this->fetchSpecificColumn($query, 'skill_bonus');
            case 'enemy-skill-reduction':
                return $this->fetchSpecificColumn($query, 'skill_reduction');
            case 'class-bonus':
                return $this->fetchSpecificColumn($query, 'class_bonus');
            case 'base-damage':
            case 'base-healing':
            case 'base-ac':
                return $this->fetchSpecificColumn($query, snake_case($this->value) . '_mod');
            case 'fight-time-out':
            case 'movement-time-out':
                return $this->fetchSpecificColumn($query, snake_case($this->value) . '_mod_bonus');
            case 'reduce-enemy-str':
            case 'reduce-enemy-dex':
            case 'reduce-enemy-agi':
            case 'reduce-enemy-dur':
            case 'reduce-enemy-int':
            case 'reduce-enemy-focus':
                return $this->fetchSpecificColumn($query,explode('-', $this->value)[2] . '_reduction');
            case 'life-stealing':
                return $this->fetchSpecificColumn($query,'steal_life_amount');
            case 'irresistible-damage':
                return $this->fetchDamage($query, false);
            case 'resistible-damage':
                return $this->fetchDamage($query, true);
            case 'entrancing':
                return $this->fetchSpecificColumn($query,'entrancing_chance');
            case 'devouring-light':
                return $this->fetchSpecificColumn($query,$this->value);
            default:
                return $query;
        }
    }

    public function getCustomColumn(): ?Column {
        switch($this->value) {
            case 'effects-str':
            case 'effects-dex':
            case 'effects-agi':
            case 'effects-dur':
            case 'effects-int':
            case 'effects-focus':
                $stat = explode('-', $this->value)[1];

                return Column::make($stat . ' % Increase', $stat . '_mod')->sortable();
            case 'skill-xp':
                return Column::make('Skill XP Bonus %', 'skill_training_bonus')->sortable();
            case 'skill-bonus':
                return Column::make('Skill Bonus %', 'skill_bonus')->sortable();
            case 'enemy-skill-reduction':
                return Column::make('Enemy Skill Reduction %', 'skill_reduction')->sortable();
            case 'class-bonus':
                return Column::make('Class Bonus %', 'class_bonus')->sortable();
            case 'base-damage':
            case 'base-healing':
            case 'base-ac':
                return Column::make(ucfirst(str_replace('-', ' ', $this->value)) . ' Mod %', snake_case($this->value) . '_mod')->sortable();
            case 'fight-time-out':
            case 'movement-time-out':
                return Column::make(ucfirst(str_replace('-', ' ', $this->value)) . ' Mod %', snake_case($this->value) . '_mod_bonus')->sortable();
            case 'reduce-enemy-str':
            case 'reduce-enemy-dex':
            case 'reduce-enemy-agi':
            case 'reduce-enemy-dur':
            case 'reduce-enemy-int':
            case 'reduce-enemy-focus':
                $stat = explode('-', $this->value)[2];

                return Column::make('Enemy ' . $stat . ' % Decrease', $stat . '_mod')->sortable();
            case 'life-stealing':
                return Column::make('Life Stealing %', 'steal_life_amount')->sortable();
            case 'irresistible-damage':
                return Column::make('Irresistible Damage', 'damage')->sortable();
            case 'resistible-damage':
                return Column::make('Resistible Damage', 'damage')->sortable();
            case 'entrancing':
                return Column::make('Entrancing', 'entrancing')->sortable();
            case 'devouring-light':
                return Column::make('Devouring Light %', 'devouring_light')->sortable();
            default:
                return null;
        }
    }

    /**
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    protected function fetchSpecificColumn(Builder $query, string $type): Builder {
        return $query->where($type, '>', 0);
    }

    /**
     * @param Builder $query
     * @param bool $irresistible
     * @return Builder
     */
    protected function fetchDamage(Builder $query, bool $irresistible): Builder {
        return $query->where('damage', '>', 0)->where('irresistible_damage', $irresistible);
    }
}
