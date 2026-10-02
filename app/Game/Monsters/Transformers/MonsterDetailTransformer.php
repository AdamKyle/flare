<?php

namespace App\Game\Monsters\Transformers;

use App\Flare\Models\Monster;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Monsters\Services\MonsterGemEffectContextService;

class MonsterDetailTransformer
{
    /**
     * @param QuestItemTransformer $questItemTransformer
     * @param MonsterGemEffectContextService $monsterGemEffectContextService
     */
    public function __construct(
        private readonly QuestItemTransformer $questItemTransformer,
        private readonly MonsterGemEffectContextService $monsterGemEffectContextService,
    ) {}

    /**
     * Transform a Monster into its full factual detail representation, preferring the effective cached payload's elemental values when one is supplied.
     *
     * @param Monster $monster
     * @param array|null $gemEffectContexts
     * @param array|null $effectiveMonster
     * @return array
     */
    public function transform(Monster $monster, ?array $gemEffectContexts = null, ?array $effectiveMonster = null): array
    {
        $gemEffectSummary = $this->resolveGemEffectSummary($monster, $gemEffectContexts);

        return [
            'id' => $monster->id,
            'identity' => $this->identity($monster),
            'combat' => $this->combat($monster),
            'probabilities' => $this->probabilities($monster),
            'spells_and_affixes' => $this->spellsAndAffixes($monster),
            'quest_and_celestial' => $this->questAndCelestial($monster),
            'raid_and_special' => $this->raidAndSpecial($monster, $effectiveMonster),
            'gem_effect_context_count' => $gemEffectSummary['count'],
            'gem_effect_context_preview' => $gemEffectSummary['preview'],
        ];
    }

    /**
     * Resolve the Gem effect context count/preview summary from an explicit current context or the factual cache-derived service.
     *
     * @param Monster $monster
     * @param array|null $gemEffectContexts
     * @return array
     */
    private function resolveGemEffectSummary(Monster $monster, ?array $gemEffectContexts): array
    {
        if (! is_null($gemEffectContexts)) {
            return [
                'count' => count($gemEffectContexts),
                'preview' => $gemEffectContexts[0] ?? null,
            ];
        }

        return $this->monsterGemEffectContextService->summary($monster);
    }

    /**
     * Build the Identity & Placement section.
     *
     * @param Monster $monster
     * @return array
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
     * @param Monster $monster
     * @return array
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
     * @param Monster $monster
     * @return array
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
     * @param Monster $monster
     * @return array
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
     * @param Monster $monster
     * @return array
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
     * Build the Raid & Special Rules section, reading elemental values from the effective cached payload when one is supplied.
     *
     * @param Monster $monster
     * @param array|null $effectiveMonster
     * @return array
     */
    private function raidAndSpecial(Monster $monster, ?array $effectiveMonster): array
    {
        $elementalSource = $effectiveMonster ?? $monster->only([
            'fire_atonement',
            'ice_atonement',
            'water_atonement',
            'fire_penetration',
            'ice_penetration',
            'water_penetration',
        ]);

        return [
            'is_raid_monster' => $monster->is_raid_monster,
            'is_raid_boss' => $monster->is_raid_boss,
            'raid_special_attack_type' => $monster->raid_special_attack_type,
            'fire_atonement' => $elementalSource['fire_atonement'] ?? null,
            'ice_atonement' => $elementalSource['ice_atonement'] ?? null,
            'water_atonement' => $elementalSource['water_atonement'] ?? null,
            'fire_penetration' => $elementalSource['fire_penetration'] ?? null,
            'ice_penetration' => $elementalSource['ice_penetration'] ?? null,
            'water_penetration' => $elementalSource['water_penetration'] ?? null,
        ];
    }
}
