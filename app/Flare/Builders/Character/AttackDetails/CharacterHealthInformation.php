<?php

namespace App\Flare\Builders\Character\AttackDetails;

use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Map;
use App\Flare\Models\Skill;
use App\Flare\Values\CharacterClassValue;
use Illuminate\Support\Collection;

class CharacterHealthInformation {

    use FetchEquipped;

    /**
     * @var CharacterInformationBuilder $characterInformationBuilder
     */
    private $characterInformationBuilder;

    /**
     * @var ClassBonuses $classBonuses
     */
    private $classBonuses;

    /**
     * @var Character $character
     */
    private $character;


    /**
     * @param CharacterInformationBuilder $characterInformationBuilder
     * @param ClassBonuses $classBonuses
     */
    public function __construct(CharacterInformationBuilder $characterInformationBuilder, ClassBonuses $classBonuses) {
        $this->characterInformationBuilder = $characterInformationBuilder;
        $this->classBonuses                = $classBonuses;
    }

    /**
     * @param Character $character
     * @return CharacterHealthInformation
     */
    public function setCharacter(Character $character): CharacterHealthInformation {
        $this->character = $character;

        $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($character);

        return $this;
    }

    /**
     * Build the amount a character can heal for.
     *
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function buildHealFor(bool $voided = false, bool $isPositional = false): int {
        $prophetBonus  = $this->characterInformationBuilder->getBaseCharacterInfo()->getClassBonuses()->prophetHealingBonus($this->character);
        $vampireBonus  = $this->characterInformationBuilder->getBaseCharacterInfo()->getClassBonuses()->getVampiresHealingBonus($this->character);

        $classBonus    = $prophetBonus + $vampireBonus;
        $class         = GameClass::find($this->character->game_class_id);

        $classType     = new CharacterClassValue($class->name);

        $healingAmount = $this->fetchHealingAmount($voided);
        $dmgStat       = $this->character->class->damage_stat;

        if ($classType->isRanger()) {
            if ($voided) {
                $healingAmount += $this->character->chr * 0.15;
            } else {
                $healingAmount += $this->characterInformationBuilder->statMod('chr') * 0.15;
            }

        }

        if ($classType->isProphet()) {
            $hasHealingSpells = $this->classBonuses->prophetHasHealingSpells($this->character);

            if ($hasHealingSpells) {
                if ($voided) {
                    $healingAmount += $this->character->{$dmgStat} * 0.30;
                } else {
                    $healingAmount += $this->characterInformationBuilder->statMod($dmgStat) * 0.30;
                }
            }
        }

        $amount = round($healingAmount + ($healingAmount * ($this->fetchSkillHealingMod() + $classBonus)));

        if ($isPositional) {
            return $amount / 2;
        }

        return $amount;
    }

    /**
     * Fetch the character Resurrection Chance
     *
     * @return float
     * @throws \Exception
     */
    public function fetchResurrectionChance(): float {
        $resurrectionItems = $this->fetchInventory()->filter(function($slot) {
            return $slot->item->can_resurrect;
        });

        $chance    = 0.0;
        $class     = GameClass::find($this->character->game_class_id);
        $classType = new CharacterClassValue($class->name);

        if ($classType->isProphet()) {
            $chance += 0.05;
        }

        if ($resurrectionItems->isEmpty()) {
            return $chance;
        }

        $chance += $resurrectionItems->sum('item.resurrection_chance');

        $map     = Map::where('character_id', $this->character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        if ($gameMap->maptype()->isPurgatory()) {
            if ($classType->isProphet()) {
                return 0.65;
            }

            return 0.45;
        }

        return $chance;
    }

    /**
     * Fetch the healing amount.
     *
     * @param bool $voided
     * @return int
     */
    protected function fetchHealingAmount(bool $voided = false): int {
        $healFor = 0;

        foreach ($this->fetchInventory() as $slot) {
            if (!$voided) {
                $healFor += $slot->item->getTotalHealing();
            } else {
                $healFor += $slot->item->base_healing;
            }
        }

        return $healFor;
    }

    /**
     * Fetch the skill healing amount modifier
     *
     * @return float
     */
    protected function fetchSkillHealingMod(): float {
        $percentageBonus = 0.0;

        $gameSkillIds = GameSkill::where('game_class_id', $this->character->game_class_id)->pluck('id')->toArray();
        $skills       = Skill::whereIn('game_skill_id', $gameSkillIds)->get();

        foreach ($skills as $skill) {
            $percentageBonus += $skill->base_healing_mod;
        }

        return $percentageBonus;
    }

    /**
     * Fetch the inventory for the character with equipped items.
     *
     * @return Collection
     */
    protected function fetchInventory(): Collection {
        $slots = $this->fetchEquipped($this->character);

        if (is_null($slots)) {
            return collect([]);
        }

        return $slots;
    }
}
