<?php

namespace App\Game\Monsters\Transformers;

use App\Flare\Models\Monster;
use App\Game\Core\Items\Transformers\QuestItemTransformer;

class MonsterDetailTransformer
{
    /**
     * @param  QuestItemTransformer  $questItemTransformer  Canonical quest Item factual transformer.
     */
    public function __construct(
        private readonly QuestItemTransformer $questItemTransformer,
    ) {}

    /**
     * Transform a Monster into its full factual detail representation.
     *
     * Every value is the Monster's persisted base value; no combat/map scaling is applied.
     * The combat `MonsterTransformer` is a gameplay concern and must never be used for this
     * static factual contract.
     *
     * @param  Monster  $monster  Monster to transform; expects `gameMap`, `questItem` eager-loaded.
     * @return array<string, mixed> Full factual Monster detail representation.
     */
    public function transform(Monster $monster): array
    {
        return [
            'id' => $monster->id,
            'identity' => $this->identity($monster),
            'combat' => $this->combat($monster),
            'probabilities' => $this->probabilities($monster),
            'spells_and_affixes' => $this->spellsAndAffixes($monster),
            'quest_and_celestial' => $this->questAndCelestial($monster),
            'raid_and_special' => $this->raidAndSpecial($monster),
        ];
    }

    /**
     * Build the Identity & Placement section.
     *
     * @param  Monster  $monster  Monster to describe.
     * @return array<string, mixed> Identity & Placement section.
     */
    private function identity(Monster $monster): array
    {
        return [
            'name' => $monster->name,
            'damage_stat' => $monster->damage_stat,
            'game_map' => is_null($monster->gameMap) ? null : ['id' => $monster->gameMap->id, 'name' => $monster->gameMap->name],
            'max_level' => $monster->max_level,
            'xp' => $monster->xp,
            'gold' => $monster->gold,
            'health_range' => $monster->health_range,
            'attack_range' => $monster->attack_range,
            'drop_check' => $monster->drop_check,
            'only_for_location_type' => $monster->only_for_location_type,
        ];
    }

    /**
     * Build the Core Combat section: base stats.
     *
     * @param  Monster  $monster  Monster to describe.
     * @return array{str: int, dur: int, dex: int, chr: int, int: int, agi: int, focus: int, ac: int} Core Combat section.
     */
    private function combat(Monster $monster): array
    {
        return [
            'str' => $monster->str,
            'dur' => $monster->dur,
            'dex' => $monster->dex,
            'chr' => $monster->chr,
            'int' => $monster->int,
            'agi' => $monster->agi,
            'focus' => $monster->focus,
            'ac' => $monster->ac,
        ];
    }

    /**
     * Build the probability fields.
     *
     * @param  Monster  $monster  Monster to describe.
     * @return array<string, float|null> Probability fields.
     */
    private function probabilities(Monster $monster): array
    {
        return [
            'accuracy' => $monster->accuracy,
            'dodge' => $monster->dodge,
            'criticality' => $monster->criticality,
            'ambush_chance' => $monster->ambush_chance,
            'ambush_resistance' => $monster->ambush_resistance,
            'counter_chance' => $monster->counter_chance,
            'counter_resistance' => $monster->counter_resistance,
        ];
    }

    /**
     * Build the Spells & Affixes section.
     *
     * @param  Monster  $monster  Monster to describe.
     * @return array<string, mixed> Spells & Affixes section.
     */
    private function spellsAndAffixes(Monster $monster): array
    {
        return [
            'can_cast' => $monster->can_cast,
            'max_spell_damage' => $monster->max_spell_damage,
            'casting_accuracy' => $monster->casting_accuracy,
            'spell_evasion' => $monster->spell_evasion,
            'max_affix_damage' => $monster->max_affix_damage,
            'affix_resistance' => $monster->affix_resistance,
            'healing_percentage' => $monster->healing_percentage,
            'entrancing_chance' => $monster->entrancing_chance,
            'devouring_light_chance' => $monster->devouring_light_chance,
            'devouring_darkness_chance' => $monster->devouring_darkness_chance,
            'life_stealing_resistance' => $monster->life_stealing_resistance,
        ];
    }

    /**
     * Build the Quest/Celestial section.
     *
     * @param  Monster  $monster  Monster to describe.
     * @return array<string, mixed> Quest/Celestial section.
     */
    private function questAndCelestial(Monster $monster): array
    {
        return [
            'quest_item' => is_null($monster->questItem) ? null : $this->questItemTransformer->transform($monster->questItem),
            'quest_item_drop_chance' => $monster->quest_item_drop_chance,
            'is_celestial_entity' => $monster->is_celestial_entity,
            'celestial_type' => $monster->celestial_type,
            'gold_cost' => $monster->gold_cost,
            'gold_dust_cost' => $monster->gold_dust_cost,
            'shards' => $monster->shards,
        ];
    }

    /**
     * Build the Raid & Special Rules section.
     *
     * @param  Monster  $monster  Monster to describe.
     * @return array<string, mixed> Raid & Special Rules section.
     */
    private function raidAndSpecial(Monster $monster): array
    {
        return [
            'is_raid_monster' => $monster->is_raid_monster,
            'is_raid_boss' => $monster->is_raid_boss,
            'raid_special_attack_type' => $monster->raid_special_attack_type,
            'fire_atonement' => $monster->fire_atonement,
            'ice_atonement' => $monster->ice_atonement,
            'water_atonement' => $monster->water_atonement,
        ];
    }
}
