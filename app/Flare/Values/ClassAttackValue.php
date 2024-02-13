<?php

namespace App\Flare\Values;

use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Character;

class ClassAttackValue {

    const VAMPIRE_THIRST            = 'vampire thirst';
    const PROPHET_HEALING           = 'prophet healing';
    const RANGER_TRIPLE_ATTACK      = 'ranger triple attack';
    const THIEVES_SHADOW_DANCE      = 'thieves shadow dance';
    const HERETICS_DOUBLE_CAST      = 'heretics double cast';
    const FIGHTERS_DOUBLE_DAMAGE    = 'double damage';
    const BLACKSMITHS_HAMMER_SMASH  = 'hammer smash';
    const ARCANE_ALCHEMISTS_DREAMS  = 'alchemists ravenous dream';
    const PRISONER_RAGE             = 'prisoner rage';
    const ALCOHOLIC_PUKE            = 'alcoholic puke';
    const MERCHANTS_SUPPLY          = 'merchants supply';
    const GUNSLINGERS_ASSASSINATION = 'gunslingers assassination';
    const SENSUAL_DANCE             = 'sensual dance';
    const BOOK_BINDERS_FEAR         = 'book binders fear';
    const HOLY_SMITE                = 'holy smite';

    private CharacterClassValue $classType;

    private Character $character;

    private CharacterStatBuilder $characterInfo;

    private array $chance = [
        'chance' => 0.05,
        'class_name' => null,
    ];

    /**
     * @param Character $character
     * @throws Exception
     */
    public function __construct(Character $character) {
        $this->classType     = new CharacterClassValue($character->class->name);
        $this->characterInfo = resolve(CharacterStatBuilder::class)->setCharacter($character);
        $this->character     = $character;
    }

    public function buildAttackData(): array {
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

        if ($this->classType->isBlacksmith()) {
            $this->buildBlacksmithsChance();

            return $this->chance;
        }

        if ($this->classType->isArcaneAlchemist()) {
            $this->buildArcaneAlchemistChance();

            return $this->chance;
        }

        if ($this->classType->isPrisoner()) {
            $this->buildPrisonerChance();

            return $this->chance;
        }

        if ($this->classType->isAlcoholic()) {
            $this->buildAlcoholicsChance();

            return $this->chance;
        }

        if ($this->classType->isMerchant()) {
            $this->buildMerchantsPlace();

            return $this->chance;
        }

        if ($this->classType->isGunslinger()) {
            $this->buildGunSlingersChance();

            return $this->chance;
        }

        if ($this->classType->isDancer()) {
            $this->buildSensualDance();

            return $this->chance;
        }

        if ($this->classType->isBookBinder()) {
            $this->buildBookBindersFear();

            return $this->chance;
        }

        if ($this->classType->isCleric()) {
            $this->buildHolySmite();

            return $this->chance;
        }

        return $this->chance;
    }

    public function buildFighterChance() {
        $this->chance['type'] = self::FIGHTERS_DOUBLE_DAMAGE;
        $this->chance['only'] = 'weapon';
        $this->chance['class_name'] = 'Fighter';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('weapon');
        $this->chance['amount'] = $this->getItemCollection('weapon')->count();
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();

    }

    public function buildProphetChance() {
        $this->chance['type'] = self::PROPHET_HEALING;
        $this->chance['only'] = 'spell-healing';
        $this->chance['class_name'] = 'Prophet';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('spell-healing');
        $this->chance['amount'] = $this->getItemCollection('spell-healing')->count();
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildThiefChance() {
        $this->chance['type'] = self::THIEVES_SHADOW_DANCE;
        $this->chance['only'] = 'weapon';
        $this->chance['class_name'] = 'Thief';
        $this->chance['has_item'] = $this->hasMultipleOfSameType('weapon', 2);
        $this->chance['amount'] = $this->getItemCollection('weapon')->count();
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildHereticChance() {
        $this->chance['type'] = self::HERETICS_DOUBLE_CAST;
        $this->chance['only'] = 'spell-damage';
        $this->chance['class_name'] = 'Heretic';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('spell-damage');
        $this->chance['amount'] = $this->getItemCollection('spell-damage')->count();
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildRangersChance() {
        $this->chance['type'] = self::RANGER_TRIPLE_ATTACK;
        $this->chance['only'] = 'bow';
        $this->chance['class_name'] = 'Ranger';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('bow');
        $this->chance['amount'] = $this->getItemCollection('bow')->count();
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildVampiresChance() {
        $this->chance['type'] = self::VAMPIRE_THIRST;
        $this->chance['class_name'] = 'Vampire';
        $this->chance['has_item'] = true;
        $this->chance['amount'] = 0;
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildBlacksmithsChance() {
        $this->chance['type'] = self::BLACKSMITHS_HAMMER_SMASH;
        $this->chance['only'] = 'hammer';
        $this->chance['class_name'] = 'Blacksmith';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('hammer');
        $this->chance['amount'] = $this->getItemCollection('hammer')->count();
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildArcaneAlchemistChance() {
        $this->chance['type'] = self::ARCANE_ALCHEMISTS_DREAMS;
        $this->chance['only'] = 'stave';
        $this->chance['class_name'] = 'Arcane Alchemist';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('stave');
        $this->chance['amount'] = $this->getItemCollection('stave')->count();
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildPrisonerChance() {
        $this->chance['type'] = self::PRISONER_RAGE;
        $this->chance['only'] = 'weapon';
        $this->chance['class_name'] = 'Prisoner';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('weapon');
        $this->chance['amount'] = $this->getItemCollection('weapon')->count();
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildAlcoholicsChance() {
        $this->chance['type']       = self::ALCOHOLIC_PUKE;
        $this->chance['only']       = 'No weapon equipped';
        $this->chance['class_name'] = 'Alcoholic';
        $this->chance['has_item']   = !$this->hasItemTypeEquipped('weapon') &&
                                      !$this->hasItemTypeEquipped('stave') &&
                                      !$this->hasItemTypeEquipped('bow') &&
                                      !$this->hasItemTypeEquipped('gun') &&
                                      !$this->hasItemTypeEquipped('fan') &&
                                      !$this->hasItemTypeEquipped('hammer') &&
                                      !$this->hasItemTypeEquipped('spell-damage') &&
                                      !$this->hasItemTypeEquipped('gun') &&
                                      !$this->hasItemTypeEquipped('fan') &&
                                      !$this->hasItemTypeEquipped('mace');
                                      !$this->hasItemTypeEquipped('spell-healing');
        $this->chance['amount']     = 0;
        $this->chance['chance']     = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildGunSlingersChance() {
        $this->chance['type']       = self::GUNSLINGERS_ASSASSINATION;
        $this->chance['only']       = 'Guns';
        $this->chance['class_name'] = 'Gunslinger';
        $this->chance['has_item']   = $this->hasItemTypeEquipped('gun');
        $this->chance['amount']     = $this->getItemCollection('gun')->count();
        $this->chance['chance']     = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildSensualDance() {
        $this->chance['type']       = self::SENSUAL_DANCE;
        $this->chance['only']       = 'Fans';
        $this->chance['class_name'] = 'Dancer';
        $this->chance['has_item']   = $this->hasItemTypeEquipped('fan');
        $this->chance['amount']     = $this->getItemCollection('fan')->count();
        $this->chance['chance']     = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildBookBindersFear() {
        $this->chance['type']       = self::BOOK_BINDERS_FEAR;
        $this->chance['only']       = 'Scatch Awls';
        $this->chance['class_name'] = 'Book Binder';
        $this->chance['has_item']   = $this->hasItemTypeEquipped('scratch-awl');
        $this->chance['amount']     = $this->getItemCollection('scratch-awl')->count();
        $this->chance['chance']     = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildHolySmite() {
        $this->chance['type']       = self::HOLY_SMITE;
        $this->chance['only']       = 'Mace and Shield';
        $this->chance['class_name'] = 'Cleric';
        $this->chance['has_item']   = $this->hasItemTypeEquipped('mace') && $this->hasItemTypeEquipped('shield');
        $this->chance['amount']     = $this->getItemCollection('mace')->count();
        $this->chance['chance']     = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    public function buildMerchantsPlace() {
        $this->chance['type'] = self::MERCHANTS_SUPPLY;
        $this->chance['only'] = 'Stave or Bow';
        $this->chance['class_name'] = 'Merchant';
        $this->chance['has_item'] = $this->hasItemTypeEquipped('stave') || $this->hasItemTypeEquipped('bow');
        $this->chance['chance'] = $this->chance['chance'] + $this->characterInfo->classBonus();
    }

    protected function hasItemTypeEquipped(string $type): bool {
        return $this->getItemCollection($type)->isNotEmpty();
    }

    protected function hasMultipleOfSameType(string $type, int $amountNeeded = 1): bool {
        return $this->getItemCollection($type)->count() === $amountNeeded;
    }

    private function getItemCollection(string $type): Collection {

        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots     = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->where('inventory_slots.equipped', true)->join('items', function($join) use ($type) {
            $join->on('items.id', '=', 'inventory_slots.item_id')
                 ->where('items.type', '=', $type);
        })->select('inventory_slots.*')->get();

        if ($slots->isEmpty()) {
            $setEquipped = $this->character->inventorySets->where('is_equipped', true)->first();

            if (!is_null($setEquipped)) {
                $slots = SetSlot::where('set_slots.inventory_set_id', $setEquipped->id)->join('items', function($join) use ($type) {
                    $join->on('items.id', '=', 'set_slots.item_id')
                        ->where('items.type', '=', $type);
                })->select('set_slots.*')->get();
            }
        }

        return $slots;
    }
}
