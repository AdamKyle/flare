<?php

namespace App\Game\Maps\Transformers;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Gem;
use App\Game\Gems\Transformers\RolledGemTransformer;
use App\Game\Gems\Values\AreaGemContext;
use App\Game\Gems\Values\GemSourceType;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Gems\Values\ResolvedAreaGemSource;

/**
 * Translates a fully resolved ResolvedAreaGemEffects result into Player-safe
 * factual Gem World context data, including backend authored explanatory
 * rules for the resolved context.
 */
class GemWorldContextTransformer
{
    public function __construct(
        private readonly RolledGemTransformer $rolledGemTransformer,
    ) {}

    /**
     * Transform the resolved Area Gem effects into the Player-facing Gem World context shape.
     */
    public function transform(ResolvedAreaGemEffects $effects): array
    {
        return [
            'type' => $effects->contextType()?->value,
            'label' => $effects->contextLabel(),
            'game_map' => $this->gameMap($effects),
            'location' => $this->location($effects),
            'rules' => $this->buildRules($effects),
            'sources' => $this->transformSources($effects),
            'character_power_reduction' => $effects->characterPowerReduction(),
            'monster_effects' => $effects->monsterEffects()->toArray(),
            'reward_effects' => $effects->rewardEffects()->toArray(),
            'crafting_skill_bonuses' => $this->craftingSkillBonuses($effects),
            'rarity_effects' => $effects->rarityEffects()->toArray(),
        ];
    }

    /**
     * Resolve the current Game Map identity, when present.
     */
    private function gameMap(ResolvedAreaGemEffects $effects): ?array
    {
        if (is_null($effects->currentGameMapId())) {
            return null;
        }

        return [
            'id' => $effects->currentGameMapId(),
            'name' => $effects->currentGameMapName(),
        ];
    }

    /**
     * Resolve the current Location identity, when present.
     */
    private function location(ResolvedAreaGemEffects $effects): ?array
    {
        if (is_null($effects->locationId())) {
            return null;
        }

        return [
            'id' => $effects->locationId(),
            'name' => $effects->locationName(),
        ];
    }

    /**
     * Resolve the positive crafting Skill bonuses, sorted by Skill name.
     */
    private function craftingSkillBonuses(ResolvedAreaGemEffects $effects): array
    {
        $bonuses = array_filter($effects->craftingSkillBonuses(), fn (float $bonus): bool => $bonus > 0.0);

        if (empty($bonuses)) {
            return [];
        }

        return GameSkill::whereIn('id', array_keys($bonuses))
            ->orderBy('name')
            ->get()
            ->map(fn (GameSkill $gameSkill): array => [
                'id' => $gameSkill->id,
                'name' => $gameSkill->name,
                'bonus' => $bonuses[$gameSkill->id],
            ])
            ->values()
            ->all();
    }

    /**
     * Transform every contributing resolved Gem source, appending its concrete rolled Gem.
     */
    private function transformSources(ResolvedAreaGemEffects $effects): array
    {
        return array_values(array_map(
            fn (ResolvedAreaGemSource $source): array => array_merge(
                $source->toArray(),
                ['rolled_gem' => $this->rolledGemTransformer->transform(Gem::findOrFail($source->rolledGemId()), true)],
            ),
            $effects->sources(),
        ));
    }

    /**
     * Build the factual explanatory rules for the resolved context.
     */
    private function buildRules(ResolvedAreaGemEffects $effects): array
    {
        return match ($effects->contextType()) {
            AreaGemContext::MAP => $this->mapRules($effects),
            AreaGemContext::LOCATION => $this->locationRules($effects),
            AreaGemContext::MAP_GEM_WORLD => $this->mapGemWorldRules($effects),
            AreaGemContext::LOCATION_GEM_WORLD => $this->locationGemWorldRules($effects),
            default => [],
        };
    }

    /**
     * Find the resolved source of the given type, when present.
     */
    private function sourceOfType(ResolvedAreaGemEffects $effects, GemSourceType $type): ?ResolvedAreaGemSource
    {
        foreach ($effects->sources() as $source) {
            if ($source->type() === $type) {
                return $source;
            }
        }

        return null;
    }

    /**
     * Build the explanatory rules for a normal, nongenerated Game Map context.
     */
    private function mapRules(ResolvedAreaGemEffects $effects): array
    {
        $mapSource = $this->sourceOfType($effects, GemSourceType::MAP_GEM);

        if (is_null($mapSource)) {
            return [];
        }

        $rules = ['The active Map Gem uses its normal rolled effect values on this Map.'];

        if ($effects->hasMonsterEffects()) {
            $rules[] = 'Map Gem Monster effects apply at the normal Map multiplier.';
        }

        if ($effects->hasRewardEffects()) {
            $rules[] = 'Map Gem player, reward, crafting, and rarity effects apply at the normal Map multiplier.';
        }

        if ($effects->characterPowerReduction() > 0.0) {
            $rules[] = 'Character power reduction comes from the Map Gem.';
        }

        return $rules;
    }

    /**
     * Build the explanatory rules for a normal, nongenerated Location context.
     */
    private function locationRules(ResolvedAreaGemEffects $effects): array
    {
        $mapSource = $this->sourceOfType($effects, GemSourceType::MAP_GEM);
        $locationSource = $this->sourceOfType($effects, GemSourceType::LOCATION_GEM);

        if (is_null($mapSource) && is_null($locationSource)) {
            return [];
        }

        if (! is_null($mapSource) && ! is_null($locationSource)) {
            return [
                'Map and Location player, reward, crafting, and rarity effects stack together.',
                'Location Gem Monster effects override Map Gem Monster effects.',
                'Character power reduction comes from the Map Gem only.',
            ];
        }

        if (! is_null($mapSource)) {
            return [
                'The Map Gem continues to provide the resolved current effects.',
                'There is no Location Gem Monster override.',
            ];
        }

        return [
            'Only the Location Gem contributes the resolved Location effects.',
            'There is no Map Gem Character power reduction.',
        ];
    }

    /**
     * Build the explanatory rules for a generated Map Gem World context.
     */
    private function mapGemWorldRules(ResolvedAreaGemEffects $effects): array
    {
        $mapSource = $this->sourceOfType($effects, GemSourceType::MAP_GEM);

        if (is_null($mapSource)) {
            return [];
        }

        $rules = [
            "The generated Gem World uses the parent Map's regular persisted Monster population.",
            'Map Gem Monster effects use the resolved Gem World Monster multiplier of ×'.$mapSource->monsterMultiplier().'.',
            'Map Gem player, reward, crafting, and rarity effects use the resolved Gem World reward multiplier of ×'.$mapSource->rewardMultiplier().'.',
        ];

        if (! is_null($mapSource->reductionMultiplier())) {
            $rules[] = 'Character power reduction uses the resolved reduction multiplier of ×'.$mapSource->reductionMultiplier().'.';
        }

        return $rules;
    }

    /**
     * Build the explanatory rules for a generated Location Gem World context.
     */
    private function locationGemWorldRules(ResolvedAreaGemEffects $effects): array
    {
        $mapSource = $this->sourceOfType($effects, GemSourceType::MAP_GEM);
        $locationSource = $this->sourceOfType($effects, GemSourceType::LOCATION_GEM);

        if (is_null($mapSource) && is_null($locationSource)) {
            return [];
        }

        $rules = ["The generated Gem World uses the parent Map's regular persisted Monster population."];

        if (! is_null($locationSource)) {
            $rules[] = 'Location Gem Monster effects override the parent Map Gem Monster effects.';
            $rules[] = 'Location Gem Monster effects use the resolved Location Gem World Monster multiplier of ×'.$locationSource->monsterMultiplier().'.';
            $rules[] = 'Location Gem player, reward, crafting, and rarity effects use the resolved Location Gem World reward multiplier of ×'.$locationSource->rewardMultiplier().'.';
        }

        if (! is_null($mapSource) && ! is_null($mapSource->reductionMultiplier())) {
            $rules[] = "Character power reduction uses the parent Map Gem's resolved reduction multiplier of ×".$mapSource->reductionMultiplier().'.';
        }

        return $rules;
    }
}
