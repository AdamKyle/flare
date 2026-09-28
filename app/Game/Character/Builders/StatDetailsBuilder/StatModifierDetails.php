<?php

namespace App\Game\Character\Builders\StatDetailsBuilder;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Builders\StatDetailsBuilder\Concerns\BasicItemDetails;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Gems\Contracts\CharacterGemEffects;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Values\CharacterGemModifierType;
use App\Game\Gems\Values\GemAbilityEffectType;
use App\Game\Gems\Values\ResolvedCharacterGemEffects;
use Facades\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ItemSkillAttribute;
use Illuminate\Support\Collection;

class StatModifierDetails
{
    use BasicItemDetails, FetchEquipped;

    private ?Collection $equipped = null;

    private ?Character $character = null;

    private ResolvedCharacterGemEffects $characterGemEffects;

    /**
     * @param CharacterStatBuilder $characterStatBuilder
     * @param CharacterAreaGemEffectService $characterAreaGemEffectService
     * @param CharacterGemEffects $characterGemEffectService
     */
    public function __construct(
        private readonly CharacterStatBuilder $characterStatBuilder,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
        private readonly CharacterGemEffects $characterGemEffectService,
    ) {}

    /**
     * Set the character.
     *
     * @return $this
     */
    public function setCharacter(Character $character): StatModifierDetails
    {

        $this->character = $character;

        $this->equipped = $this->fetchEquipped($character);
        $this->characterGemEffects = $this->characterGemEffectService->resolveForCharacterId($character->id);

        return $this;
    }

    /**
     * Return the persisted, modified, and source details for one raw Character stat.
     *
     * @param string $stat
     * @return array
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
        $modifierType = CharacterGemModifierType::fromStatKey($stat);
        $details['gem_details'] = is_null($modifierType) ? [] : $this->characterGemEffects->detailsFor($modifierType);

        return $details;
    }

    /**
     * Build the requested Character combat-stat breakdown.
     *
     * @param string $type
     * @param bool $isVodied
     * @return array
     */
    public function buildSpecificBreakDown(string $type, bool $isVodied = false): array
    {
        return match ($type) {
            'health' => $this->fetchHealthBreakDown($isVodied),
            'ac' => $this->buildDefenceBreakDown($isVodied),
            'weapon_damage' => $this->buildDamageBreakDown(ItemType::validWeapons(), $isVodied),
            'spell_damage' => $this->buildDamageBreakDown([ItemType::SPELL_DAMAGE->value], $isVodied),
            'ring_damage' => $this->buildDamageBreakDown([ItemType::RING->value], $isVodied),
            'heal_for' => $this->buildDamageBreakDown([ItemType::SPELL_HEALING->value], $isVodied),
            default => [],
        };
    }

    /**
     * Build the Character health breakdown.
     *
     * @param bool $isVoided
     * @return array
     */
    public function fetchHealthBreakDown(bool $isVoided): array
    {
        $details = [];

        $details['stat_amount'] = $this->character->getInformation()->statMod('dur', $isVoided);
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesForHealth();
        $details['items_equipped'] = array_values($this->fetchItemDetails('dur'));
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();

        return $details;
    }

    /**
     * Build the Character defence breakdown with Gem modifier sources.
     *
     * @param bool $isVoided
     * @return array
     */
    public function buildDefenceBreakDown(bool $isVoided): array
    {

        $details = [];

        $details['class_bonus_details'] = $this->fetchClassBonusesEffecting('base_ac');
        $details['boon_details'] = $this->fetchBoonDetails('base_ac');
        $details['class_specialties'] = $this->fetchClassRankSpecialtiesDetails('base_ac');
        $details['ancestral_item_skill_data'] = $this->fetchAncestralItemSkills('base_ac');
        $details['items_equipped'] = array_values($this->fetchItemDetails('base_ac'));
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();
        $details['gem_modifier_details'] = [
            ...$this->characterGemEffects->detailsFor(CharacterGemModifierType::BASE_AC_MOD),
            ...$this->characterGemEffects->passiveDetailsFor(GemAbilityEffectType::DEFENCE_MOD->value),
        ];

        return array_merge($details, $this->character->getInformation()->getDefenceBuilder()->buildDefenceBreakDownDetails($isVoided));
    }

    /**
     * Build a weapon, spell, ring, or healing breakdown with Gem modifier sources.
     *
     * @param string|array $type
     * @param bool $isVoided
     * @return array
     */
    public function buildDamageBreakDown(string|array $type, bool $isVoided): array
    {
        $details = [];
        $types = is_array($type) ? $type : [$type];
        $isWeaponDamage = ! empty(array_intersect($types, ItemType::validWeapons()));
        $isSpellDamage = in_array(ItemType::SPELL_DAMAGE->value, $types);
        $isRingDamage = in_array(ItemType::RING->value, $types);
        $isHealing = in_array(ItemType::SPELL_HEALING->value, $types);

        $damageStatAmount = $this->character->getInformation()->statMod($this->character->damage_stat, $isVoided);

        $details['damage_stat_name'] = $this->character->damage_stat;
        $details['damage_stat_amount'] = $damageStatAmount;
        $details['non_equipped_damage_amount'] = 0;
        $details['non_equipped_percentage_of_stat_used'] = 0;
        $details['spell_damage_stat_amount_to_use'] = 0;
        $details['percentage_of_stat_used'] = 0;
        $details['total_damage_for_type'] = $this->character->getInformation()->buildDamage($types, $isVoided);
        $details['base_damage'] = 0;
        $details['items_equipped'] = $this->fetchDamageOrHealingEquipmentBreakDown($types);
        $details['map_reduction'] = $this->getMapCharacterReductionsDetails();

        $details = $this->setNonEquippedDamageDetails($details, $types, $damageStatAmount);

        if (is_null($this->equipped)) {
            if ($isWeaponDamage) {
                if ($this->character->classType()->isAlcoholic()) {
                    $value = $damageStatAmount * 0.25;

                    $details['non_equipped_damage_amount'] = max($value, 5);
                    $details['non_equipped_percentage_of_stat_used'] = 0.25;
                } elseif ($this->character->classType()->isFighter()) {
                    $value = $damageStatAmount * 0.05;

                    $details['non_equipped_damage_amount'] = max($value, 5);
                    $details['non_equipped_percentage_of_stat_used'] = 0.05;
                } else {
                    $value = $damageStatAmount * 0.02;

                    $details['non_equipped_damage_amount'] = max($value, 5);
                    $details['non_equipped_percentage_of_stat_used'] = 0.02;
                }
            }

            if ($isSpellDamage && $this->character->classType()->isHeretic()) {
                $value = $damageStatAmount * 0.15;

                $details['spell_damage_stat_amount_to_use'] = max($value, 5);
                $details['percentage_of_stat_used'] = 0.15;
            }
        }

        $classSpecialtyStat = match (true) {
            $isSpellDamage => 'base_spell_damage',
            $isHealing => 'base_healing',
            default => 'base_damage',
        };

        $details['class_bonus_details'] = $isRingDamage ? null : $this->fetchClassBonusesEffecting('base_damage');
        $details['boon_details'] = $isRingDamage ? null : $this->fetchBoonDetails('base_damage');
        $details['class_specialties'] = $isRingDamage ? null : $this->fetchClassRankSpecialtiesDetails($classSpecialtyStat);
        $details['ancestral_item_skill_data'] = $isRingDamage ? [] : $this->fetchAncestralItemSkills('base_damage');
        $gemModifierType = match (true) {
            $isWeaponDamage => CharacterGemModifierType::BASE_DAMAGE_MOD,
            $isSpellDamage => CharacterGemModifierType::BASE_SPELL_DAMAGE_MOD,
            $isHealing => CharacterGemModifierType::BASE_HEALING_MOD,
            default => null,
        };
        $details['gem_modifier_details'] = is_null($gemModifierType)
            ? []
            : $this->characterGemEffects->detailsFor($gemModifierType);

        $passiveEffectType = match (true) {
            $isWeaponDamage => GemAbilityEffectType::WEAPON_DAMAGE_MOD->value,
            $isSpellDamage => GemAbilityEffectType::SPELL_DAMAGE_MOD->value,
            $isHealing => GemAbilityEffectType::HEALING_MOD->value,
            default => null,
        };

        if (! is_null($passiveEffectType)) {
            $details['gem_modifier_details'] = [
                ...$details['gem_modifier_details'],
                ...$this->characterGemEffects->passiveDetailsFor($passiveEffectType),
            ];
        }

        $typeAttributes = match (true) {
            $isWeaponDamage => $this->character->getInformation()->getDamageBuilder()->buildWeaponDamageBreakDown($damageStatAmount, $isVoided),
            $isSpellDamage => $this->character->getInformation()->getDamageBuilder()->buildSpellDamageBreakDownDetails($isVoided),
            $isRingDamage => $this->character->getInformation()->getDamageBuilder()->buildRingDamageBreakDown(),
            $isHealing => $this->character->getInformation()->getHealingBuilder()->getHealingBuilder($isVoided),
            default => [],
        };

        return array_merge($details, $typeAttributes);
    }

    /**
     * Add the class-specific fallback damage details used when no weapon is equipped.
     *
     * @param array $details
     * @param array $types
     * @param float $damageStatAmount
     * @return array
     */
    private function setNonEquippedDamageDetails(array $details, array $types, float $damageStatAmount): array
    {
        $percentage = 0.02;

        if (! empty(array_intersect($types, ItemType::validWeapons()))) {
            if ($this->character->classType()->isAlcoholic()) {
                $percentage = 0.25;
            } elseif ($this->character->classType()->isFighter()) {
                $percentage = 0.05;
            }
        }

        $value = $damageStatAmount * $percentage;

        $details['non_equipped_damage_amount'] = max($value, 5);
        $details['non_equipped_percentage_of_stat_used'] = $percentage;

        return $details;
    }

    /**
     * Return equipped Item contribution rows for damage or healing types.
     *
     * @param array $types
     * @return array
     */
    private function fetchDamageOrHealingEquipmentBreakDown(array $types): array
    {
        if (in_array(ItemType::RING->value, $types)) {
            return [];
        }

        if (in_array(ItemType::SPELL_HEALING->value, $types)) {
            return array_values($this->fetchItemDetails('base_healing'));
        }

        return array_values($this->fetchItemDetails('base_damage'));
    }

    /**
     * Return the matching current-class Skill bonus for one attribute.
     *
     * @param string $attribute
     * @return array|null
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
     * Return the combined Map and Area Gem Character power reduction details.
     *
     * @return array|null
     */
    private function getMapCharacterReductionsDetails(): ?array
    {
        $map = $this->character->map->gameMap;

        $legacyReduction = $this->resolveLegacyMapReduction($map);
        $gemReduction = $this->characterAreaGemEffectService->resolveForCharacter($this->character)->characterPowerReduction();

        $totalReduction = $legacyReduction + $gemReduction;

        if ($totalReduction <= 0.0) {
            return null;
        }

        return [
            'map_name' => $map->name,
            'reduction_amount' => $totalReduction,
        ];
    }

    /**
     * Resolve the legacy non-Gem Map Character power reduction when applicable.
     *
     * @param GameMap $map
     * @return float
     */
    private function resolveLegacyMapReduction(GameMap $map): float
    {
        if (
            $map->mapType()->isHell() ||
            $map->mapType()->isPurgatory() ||
            $map->mapType()->isTwistedMemories()
        ) {
            return $map->character_attack_reduction ?? 0.0;
        }

        $purgatoryQuestItem = $this->character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectType::PURGATORY->value;
        })->first();

        if (! is_null($purgatoryQuestItem) && ($map->mapType()->isTheIcePlane() || $map->mapType()->isDelusionalMemories())) {
            return $map->character_attack_reduction ?? 0.0;
        }

        return 0.0;
    }

    /**
     * Return Ancestral Item Skill contributions for one stat.
     *
     * @param string $stat
     * @return array
     */
    private function fetchAncestralItemSkills(string $stat): array
    {
        $artifact = ItemSkillAttribute::fetchArtifactItemEquipped($this->character);

        if (is_null($artifact)) {
            return [];
        }

        $itemSkills = ItemSkillAttribute::fetchItemSkillsThatEffectStat($artifact, $stat);

        if ($itemSkills->isEmpty()) {
            return [];
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
     * Return equipped Class Mastery contributions for one stat.
     *
     * @param string $stat
     * @return array
     */
    private function fetchClassRankSpecialtiesDetails(string $stat): array
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

            return $details;
        }

        $modifier = $stat.'_mod';

        $classSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where($modifier, '>', 0);

        foreach ($classSpecialties as $classSpecialty) {
            $details[] = [
                'name' => $classSpecialty->gameClassSpecial->name,
                'amount' => $classSpecialty->{$modifier},
            ];
        }

        return $details;
    }

    /**
     * Return equipped Class Mastery contributions that affect health.
     *
     * @return array
     */
    private function fetchClassRankSpecialtiesForHealth(): array
    {
        $details = [];

        $classSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where('base_damage_stat_increase', '>', 0);

        foreach ($classSpecialties as $classSpecialty) {
            $details[] = [
                'name' => $classSpecialty->gameClassSpecial->name,
                'amount' => $classSpecialty->base_damage_stat_increase,
            ];
        }

        $healthSpecialties = $this->character->classSpecialsEquipped
            ->where('equipped', '=', true)
            ->where('health_mod', '>', 0);

        foreach ($healthSpecialties as $classSpecialty) {
            $details[] = [
                'name' => $classSpecialty->gameClassSpecial->name,
                'amount' => $classSpecialty->health_mod,
            ];
        }

        return $details;
    }

    /**
     * Return active boon contributions for one stat.
     *
     * @param string $stat
     * @return array|null
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

            foreach ($applyToSpecificStat as $boon) {

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
     * Return equipped Item and affix contributions for one stat.
     *
     * @param string $stat
     * @param bool $isVoided
     * @return array
     */
    private function fetchItemDetails(string $stat, bool $isVoided = false): array
    {
        if (is_null($this->equipped)) {
            return [];
        }

        $details = [];

        foreach ($this->equipped as $slot) {
            $details[$slot->item->affix_name] = [];

            $details[$slot->item->affix_name]['item_base_stat'] = $slot->item->{$stat.'_mod'} ?? 0;
            $details[$slot->item->affix_name]['item_details'] = $this->getBasicDetailsOfItem($slot->item);
            $details[$slot->item->affix_name]['total_stat_increase'] = $isVoided ? 0 : $slot->item->holy_stack_stat_bonus;
            $details[$slot->item->affix_name]['attached_affixes'] = $isVoided ? [] : $this->fetchStatDetailsFromEquipment($slot->item, $stat)['attached_affixes'];
        }

        return $details;
    }

    /**
     * Return prefix and suffix contributions from one equipped Item.
     *
     * @param Item $item
     * @param string $stat
     * @return array
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
                    'name' => $itemPrefix->name,
                    $stat.'_mod' => $itemPrefix->{$stat.'_mod'},
                ];
            }
        }

        if (! is_null($itemSuffix)) {
            $statAmount = $itemSuffix->{$stat.'_mod'};

            if ($statAmount > 0) {
                $details['attached_affixes'][] = [
                    'name' => $itemSuffix->name,
                    $stat.'_mod' => $itemSuffix->{$stat.'_mod'},
                ];
            }
        }

        return $details;
    }
}
