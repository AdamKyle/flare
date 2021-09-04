<?php

namespace App\Flare\Values;

use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Character;

class ClassAttackValue {

    const VAMPIRE_THIRST = 'vampire thirst';
    const PROPHET_HEALING = 'prophet healing';
    const RANGER_TRIPLE_ATTACK = 'ranger triple attack';
    const THIEVES_SHADOW_DANCE = 'thieves shadow dance';
    const HERETICS_DOUBLE_CAST = 'heretics double cast';
    const FIGHTERS_DOUBLE_DAMAGE = 'double damage';

    private $classType;

    private $character;

    private $chance = [
        'chance' => 0.05,
        'class_name' => null,
    ];

    public function __construct(Character $character) {
        $this->classType = new CharacterClassValue($character->class->name);
        $this->character = $character;
    }

    public function buildAttackData() {
        if ($this->classType->isFighter()) {
            $this->buildFighterChance();

            return $this->chance;
        }

        if ($this->classType->isProphet()) {
            $this->buildProphetChance();

            return $this->chance;
        }

        if ($this->classType->isThief()) {
            $this->buildThiefChance();

            return $this->chance;
        }

        if ($this->classType->isRanger()) {
            $this->buildRangersChance();

            return $this->chance;
        }

        if ($this->classType->isHeretic()) {
            $this->buildHereticChance();

            return $this->chance;
        }

        if ($this->classType->isVampire()) {
            $this->buildVampiresChance();

            return $this->chance;
        }
    }

    public function buildFighterChance() {
        $this->chance['type'] = self::FIGHTERS_DOUBLE_DAMAGE;
        $this->chance['only'] = 'weapon';
        $this->chance['class_name'] = 'Fighter';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('weapon');

    }

    public function buildProphetChance() {
        $this->chance['type'] = self::PROPHET_HEALING;
        $this->chance['only'] = 'spell-healing';
        $this->chance['class_name'] = 'Prophet';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('spell-healing');
    }

    public function buildThiefChance() {
        $this->chance['type'] = self::THIEVES_SHADOW_DANCE;
        $this->chance['only'] = 'weapon';
        $this->chance['class_name'] = 'Thief';
        $this->chance['has_item'] = $this->hasMultipleOfSameType('weapon', 2);
    }

    public function buildHereticChance() {
        $this->chance['type'] = SELF::HERETICS_DOUBLE_CAST;
        $this->chance['only'] = 'spell-damage';
        $this->chance['class_name'] = 'Heretic';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('spell-damage');
    }

    public function buildRangersChance() {
        $this->chance['type'] = Self::RANGER_TRIPLE_ATTACK;
        $this->chance['only'] = 'bow';
        $this->chance['class_name'] = 'Ranger';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('bow');
    }

    public function buildVampiresChance() {
        $this->chance['type'] = Self::VAMPIRE_THIRST;
        $this->chance['class_name'] = 'Vampire';
        $this->chance['has_item'] = true;
    }

    protected function hasItemTypeEquipped(string $type): bool {
        return $this->getItemCollection($type)->isNotEmpty();
    }

    protected function hasMultipleOfSameType(string $type, int $amountNeeded = 1): bool {
        return $this->getItemCollection($type)->count() === $amountNeeded;
    }

    private function getItemCollection(string $type): Collection {
        $items = $this->character->inventory->slots->filter(function($slot) use($type) {
            return $slot->item->type === $type && $slot->equipped;
        });

        if ($items->isEmpty()) {
            $setEquipped = $this->character->inventorySets->where('is_equipped', true)->first();

            if (!is_null($setEquipped)) {
                $items = $setEquipped->slots->filter(function($slot) use($type) {
                    return $slot->item->type === $type && $slot->equipped;
                });
            }
        }

        return $items;
    }
}
